<div class="pc3d-quote">
  <h2>{l s='3D Print Quote' mod='pcproduct3dcalculator'}</h2>
  <form method="post" enctype="multipart/form-data" action="{$upload_action}" class="pc3d-quote__form">
    <div class="form-group">
      <label for="pc3d-file">{l s='Upload STL or OBJ' mod='pcproduct3dcalculator'}</label>
      <input type="file" name="pc3d_file" id="pc3d-file" accept=".stl,.obj" required>
      <p class="help-block">
        {l s='Max file size:' mod='pcproduct3dcalculator'} {$max_file_size_mb}MB — {l s='Allowed:' mod='pcproduct3dcalculator'} {', '|implode($allowed_extensions)}
      </p>
    </div>

    <div class="form-group">
      <label for="pc3d-material">{l s='Material' mod='pcproduct3dcalculator'}</label>
      <select id="pc3d-material" name="pc3d_material" class="form-control">
        <option value="pla">PLA</option>
        <option value="abs">ABS</option>
        <option value="petg">PETG</option>
      </select>
    </div>

    <div class="form-group">
      <label for="pc3d-infill">{l s='Infill %' mod='pcproduct3dcalculator'}</label>
      <input type="number" id="pc3d-infill" name="pc3d_infill" value="20" min="0" max="100" step="5" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">
      {l s='Get estimate' mod='pcproduct3dcalculator'}
    </button>
  </form>
</div>
