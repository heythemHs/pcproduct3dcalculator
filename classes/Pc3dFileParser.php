<?php
/**
 * 3D File Parser for STL and OBJ files
 * Calculates volume using signed tetrahedron method
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pc3dFileParser
{
    const FORMAT_STL_ASCII = 'stl_ascii';
    const FORMAT_STL_BINARY = 'stl_binary';
    const FORMAT_OBJ = 'obj';

    private $filePath;
    private $format;
    private $vertices = [];
    private $triangles = [];

    /**
     * Parse a 3D file and calculate its volume
     *
     * @param string $filePath Path to the STL or OBJ file
     * @return array ['volume' => float, 'unit' => string, 'triangles' => int]
     * @throws Exception on parsing errors
     */
    public static function calculateVolume($filePath)
    {
        $parser = new self($filePath);
        return $parser->parse();
    }

    public function __construct($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $this->filePath = $filePath;
        $this->detectFormat();
    }

    private function detectFormat()
    {
        $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        if ($extension === 'obj') {
            $this->format = self::FORMAT_OBJ;
            return;
        }

        if ($extension === 'stl') {
            // Check if ASCII or binary STL
            $handle = fopen($this->filePath, 'rb');
            $header = fread($handle, 80);
            fclose($handle);

            // ASCII STL starts with "solid"
            if (strpos(trim($header), 'solid') === 0) {
                // Double check - some binary files start with "solid" in header
                $content = file_get_contents($this->filePath, false, null, 0, 1000);
                if (preg_match('/facet\s+normal/i', $content)) {
                    $this->format = self::FORMAT_STL_ASCII;
                    return;
                }
            }

            $this->format = self::FORMAT_STL_BINARY;
            return;
        }

        throw new Exception('Unsupported file format: ' . $extension);
    }

    public function parse()
    {
        switch ($this->format) {
            case self::FORMAT_STL_ASCII:
                $this->parseStlAscii();
                break;
            case self::FORMAT_STL_BINARY:
                $this->parseStlBinary();
                break;
            case self::FORMAT_OBJ:
                $this->parseObj();
                break;
        }

        $volume = $this->calculateSignedVolume();

        return [
            'volume_cm3' => abs($volume) / 1000, // Convert mm³ to cm³
            'volume_mm3' => abs($volume),
            'triangles' => count($this->triangles),
            'format' => $this->format,
        ];
    }

    private function parseStlAscii()
    {
        $content = file_get_contents($this->filePath);

        // Extract all vertices from facets
        preg_match_all('/vertex\s+([-\d.eE+]+)\s+([-\d.eE+]+)\s+([-\d.eE+]+)/i', $content, $matches, PREG_SET_ORDER);

        $vertexIndex = 0;
        $currentTriangle = [];

        foreach ($matches as $match) {
            $vertex = [
                (float) $match[1],
                (float) $match[2],
                (float) $match[3],
            ];

            $this->vertices[] = $vertex;
            $currentTriangle[] = $vertexIndex;
            $vertexIndex++;

            if (count($currentTriangle) === 3) {
                $this->triangles[] = $currentTriangle;
                $currentTriangle = [];
            }
        }
    }

    private function parseStlBinary()
    {
        $handle = fopen($this->filePath, 'rb');

        // Skip 80-byte header
        fseek($handle, 80);

        // Read number of triangles (4 bytes, unsigned int)
        $numTriangles = unpack('V', fread($handle, 4))[1];

        for ($i = 0; $i < $numTriangles; $i++) {
            // Skip normal vector (12 bytes)
            fseek($handle, 12, SEEK_CUR);

            $triangle = [];

            // Read 3 vertices (each 12 bytes: 3 floats)
            for ($j = 0; $j < 3; $j++) {
                $data = fread($handle, 12);
                if (strlen($data) < 12) {
                    break 2;
                }

                $coords = unpack('f3', $data);
                $vertex = [
                    $coords[1],
                    $coords[2],
                    $coords[3],
                ];

                $this->vertices[] = $vertex;
                $triangle[] = count($this->vertices) - 1;
            }

            $this->triangles[] = $triangle;

            // Skip attribute byte count (2 bytes)
            fseek($handle, 2, SEEK_CUR);
        }

        fclose($handle);
    }

    private function parseObj()
    {
        $content = file_get_contents($this->filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            $type = array_shift($parts);

            if ($type === 'v') {
                // Vertex
                $this->vertices[] = [
                    (float) ($parts[0] ?? 0),
                    (float) ($parts[1] ?? 0),
                    (float) ($parts[2] ?? 0),
                ];
            } elseif ($type === 'f') {
                // Face - can be triangles or polygons
                $faceVertices = [];

                foreach ($parts as $part) {
                    // Format can be: v, v/vt, v/vt/vn, v//vn
                    $indices = explode('/', $part);
                    $vertexIndex = (int) $indices[0];

                    // OBJ indices are 1-based
                    $faceVertices[] = $vertexIndex - 1;
                }

                // Triangulate polygons (fan triangulation)
                for ($i = 1; $i < count($faceVertices) - 1; $i++) {
                    $this->triangles[] = [
                        $faceVertices[0],
                        $faceVertices[$i],
                        $faceVertices[$i + 1],
                    ];
                }
            }
        }
    }

    /**
     * Calculate volume using signed tetrahedron method
     * Each triangle forms a tetrahedron with the origin
     * Sum of signed volumes gives total volume
     */
    private function calculateSignedVolume()
    {
        $totalVolume = 0.0;

        foreach ($this->triangles as $triangle) {
            if (count($triangle) !== 3) {
                continue;
            }

            $v0 = $this->vertices[$triangle[0]] ?? null;
            $v1 = $this->vertices[$triangle[1]] ?? null;
            $v2 = $this->vertices[$triangle[2]] ?? null;

            if (!$v0 || !$v1 || !$v2) {
                continue;
            }

            // Signed volume of tetrahedron formed with origin
            // V = (1/6) * |a · (b × c)|
            $volume = $this->signedTetrahedronVolume($v0, $v1, $v2);
            $totalVolume += $volume;
        }

        return $totalVolume;
    }

    private function signedTetrahedronVolume($v0, $v1, $v2)
    {
        // Cross product of v1 and v2
        $cross = [
            $v1[1] * $v2[2] - $v1[2] * $v2[1],
            $v1[2] * $v2[0] - $v1[0] * $v2[2],
            $v1[0] * $v2[1] - $v1[1] * $v2[0],
        ];

        // Dot product with v0
        $dot = $v0[0] * $cross[0] + $v0[1] * $cross[1] + $v0[2] * $cross[2];

        return $dot / 6.0;
    }

    /**
     * Validate uploaded file
     *
     * @param array $file $_FILES array element
     * @param int $maxSizeMb Maximum file size in MB
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validateUpload($file, $maxSizeMb = 10)
    {
        $allowedExtensions = ['stl', 'obj'];
        $allowedMimes = [
            'application/octet-stream',
            'application/sla',
            'model/stl',
            'model/obj',
            'text/plain',
            'application/vnd.ms-pki.stl',
        ];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server maximum size',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            ];

            return [
                'valid' => false,
                'error' => $errors[$file['error']] ?? 'Unknown upload error',
            ];
        }

        // Check file size
        $maxBytes = $maxSizeMb * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return [
                'valid' => false,
                'error' => sprintf('File size exceeds %d MB limit', $maxSizeMb),
            ];
        }

        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions),
            ];
        }

        // Check MIME type (be lenient as MIME detection varies)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        // STL/OBJ files often detected as application/octet-stream or text/plain
        if (!in_array($mime, $allowedMimes)) {
            // Additional check: try to read file header
            $handle = fopen($file['tmp_name'], 'rb');
            $header = fread($handle, 100);
            fclose($handle);

            $isValid = false;

            if ($extension === 'stl') {
                // Check for ASCII STL header or binary STL
                $isValid = (strpos($header, 'solid') === 0) || strlen($header) >= 80;
            } elseif ($extension === 'obj') {
                // Check for common OBJ content
                $isValid = preg_match('/^(#|v\s|vt\s|vn\s|f\s|o\s|g\s|mtllib|usemtl)/m', $header);
            }

            if (!$isValid) {
                return [
                    'valid' => false,
                    'error' => 'File content does not match expected format',
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }
}
