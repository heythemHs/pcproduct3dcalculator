{extends file='page.tpl'}

{block name='page_title'}
  {l s='3D Print Quote Calculator' mod='pcproduct3dcalculator'}
{/block}

{block name='page_content'}
<div class="pc3d-quote-page">

  {* Alert Messages *}
  <div class="pc3d-alert-success alert alert-success" style="display:none;"></div>
  <div class="pc3d-alert-error alert alert-danger" style="display:none;"></div>

  {* Loading Overlay *}
  <div class="pc3d-loading" style="display:none;">
    <div class="pc3d-loading-spinner"></div>
    <span class="pc3d-loading-text">{l s='Processing...' mod='pcproduct3dcalculator'}</span>
  </div>

  <div class="row">
    {* Left Column - Upload & Options *}
    <div class="col-lg-5 col-md-6">
      <div class="pc3d-card">
        <h3 class="pc3d-card-title">
          <i class="material-icons">cloud_upload</i>
          {l s='Upload Your 3D File' mod='pcproduct3dcalculator'}
        </h3>

        <form class="pc3d-form" method="post" enctype="multipart/form-data">
          {* Drop Zone *}
          <div class="pc3d-drop-zone">
            <input type="file" name="pc3d_file" class="pc3d-file-input" accept=".stl,.obj" hidden>
            <div class="pc3d-drop-zone-icon">
              <i class="material-icons">insert_drive_file</i>
            </div>
            <p class="pc3d-drop-zone-text">
              {l s='Drag & drop your STL or OBJ file here' mod='pcproduct3dcalculator'}<br>
              <small>{l s='or click to browse' mod='pcproduct3dcalculator'}</small>
            </p>
            <p class="pc3d-file-name" style="display:none;"></p>
            <p class="pc3d-file-info">
              {l s='Max file size:' mod='pcproduct3dcalculator'} {$pc3d_max_file_size}MB
            </p>
          </div>

          {* Material Selection *}
          <div class="form-group pc3d-form-group">
            <label for="pc3d-material">{l s='Select Material' mod='pcproduct3dcalculator'}</label>
            <select id="pc3d-material" name="pc3d_material" class="form-control pc3d-material-select">
              <option value="">{l s='-- Choose a material --' mod='pcproduct3dcalculator'}</option>
              {foreach from=$pc3d_materials item=material}
                <option value="{$material.id_pc3d_material}"
                        data-density="{$material.density}"
                        data-price="{$material.price_per_gram}"
                        data-color="{$material.color|escape:'htmlall':'UTF-8'}">
                  {$material.name|escape:'htmlall':'UTF-8'}
                  {if $material.description} - {$material.description|strip_tags|truncate:50|escape:'htmlall':'UTF-8'}{/if}
                </option>
              {/foreach}
            </select>
          </div>

          {* Infill Slider *}
          <div class="form-group pc3d-form-group">
            <label for="pc3d-infill">
              {l s='Infill Percentage' mod='pcproduct3dcalculator'}
              <span class="pc3d-infill-value">{$pc3d_default_infill}%</span>
            </label>
            <input type="range"
                   id="pc3d-infill"
                   name="pc3d_infill"
                   class="form-control-range pc3d-infill-input"
                   min="0"
                   max="100"
                   step="5"
                   value="{$pc3d_default_infill}">
            <div class="pc3d-infill-labels">
              <span>0%</span>
              <span>50%</span>
              <span>100%</span>
            </div>
          </div>
        </form>
      </div>

      {* Results Card *}
      <div class="pc3d-card pc3d-results" style="display:none;">
        <h3 class="pc3d-card-title">
          <i class="material-icons">assessment</i>
          {l s='Quote Results' mod='pcproduct3dcalculator'}
        </h3>

        <div class="pc3d-results-table">
          {if $pc3d_show_volume}
          <div class="pc3d-result-row">
            <span class="pc3d-result-label">{l s='Volume:' mod='pcproduct3dcalculator'}</span>
            <span class="pc3d-result-value pc3d-result-volume">-</span>
          </div>
          {/if}

          {if $pc3d_show_weight}
          <div class="pc3d-result-row">
            <span class="pc3d-result-label">{l s='Estimated Weight:' mod='pcproduct3dcalculator'}</span>
            <span class="pc3d-result-value pc3d-result-weight">-</span>
          </div>
          {/if}

          <div class="pc3d-result-row">
            <span class="pc3d-result-label">{l s='Material:' mod='pcproduct3dcalculator'}</span>
            <span class="pc3d-result-value pc3d-result-material">-</span>
          </div>
        </div>

        <div class="pc3d-price-display">
          <div class="pc3d-price-label">{l s='Estimated Price' mod='pcproduct3dcalculator'}</div>
          <div class="pc3d-price-value pc3d-result-price">-</div>
        </div>

        <div class="pc3d-actions">
          <button type="button" class="btn btn-primary btn-lg pc3d-add-to-cart" disabled>
            <i class="material-icons">shopping_cart</i>
            {l s='Add to Cart' mod='pcproduct3dcalculator'}
          </button>

          {if $pc3d_allow_quote}
          <button type="button" class="btn btn-outline-secondary pc3d-request-quote">
            <i class="material-icons">mail_outline</i>
            {l s='Request Quote' mod='pcproduct3dcalculator'}
          </button>
          {/if}
        </div>
      </div>
    </div>

    {* Right Column - 3D Viewer *}
    <div class="col-lg-7 col-md-6">
      <div class="pc3d-card pc3d-viewer-card">
        <h3 class="pc3d-card-title">
          <i class="material-icons">view_in_ar</i>
          {l s='3D Model Preview' mod='pcproduct3dcalculator'}
        </h3>

        <div class="pc3d-viewer-wrapper">
          {* Viewer Container *}
          <div class="pc3d-viewer-container">
            <div id="pc3d-viewer"></div>

            {* Placeholder when no model loaded *}
            <div class="pc3d-viewer-placeholder">
              <i class="material-icons">view_in_ar</i>
              <p>{l s='Upload a 3D file to see preview' mod='pcproduct3dcalculator'}</p>
            </div>

            {* Loading indicator for viewer *}
            <div class="pc3d-viewer-loading" style="display:none;">
              <div class="pc3d-loading-spinner"></div>
              <span>{l s='Loading model...' mod='pcproduct3dcalculator'}</span>
            </div>
          </div>

          {* Viewer Controls *}
          <div class="pc3d-viewer-controls" style="display:none;">
            <div class="pc3d-viewer-controls-left">
              <button type="button" class="btn btn-sm btn-outline-secondary pc3d-viewer-rotate active" title="{l s='Auto Rotate' mod='pcproduct3dcalculator'}">
                <i class="material-icons">360</i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary pc3d-viewer-wireframe" title="{l s='Wireframe' mod='pcproduct3dcalculator'}">
                <i class="material-icons">grid_on</i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary pc3d-viewer-reset" title="{l s='Reset View' mod='pcproduct3dcalculator'}">
                <i class="material-icons">center_focus_strong</i>
              </button>
            </div>
            <div class="pc3d-viewer-controls-right">
              <label class="pc3d-color-picker">
                <span>{l s='Color:' mod='pcproduct3dcalculator'}</span>
                <input type="color" class="pc3d-viewer-color" value="#2fb5d2">
              </label>
              <button type="button" class="btn btn-sm btn-outline-secondary pc3d-viewer-fullscreen" title="{l s='Fullscreen' mod='pcproduct3dcalculator'}">
                <i class="material-icons">fullscreen</i>
              </button>
            </div>
          </div>
        </div>

        {* Viewer Instructions *}
        <div class="pc3d-viewer-instructions">
          <small>
            <strong>{l s='Controls:' mod='pcproduct3dcalculator'}</strong>
            {l s='Left click + drag to rotate' mod='pcproduct3dcalculator'} |
            {l s='Right click + drag to pan' mod='pcproduct3dcalculator'} |
            {l s='Scroll to zoom' mod='pcproduct3dcalculator'}
          </small>
        </div>
      </div>

      {* Existing Uploads *}
      {if $pc3d_uploads|@count > 0}
      <div class="pc3d-card pc3d-existing-uploads">
        <h3 class="pc3d-card-title">
          <i class="material-icons">history</i>
          {l s='Your Recent Uploads' mod='pcproduct3dcalculator'}
        </h3>

        <div class="pc3d-uploads-list">
          {foreach from=$pc3d_uploads item=upload}
          <div class="pc3d-upload-item" data-upload-id="{$upload.id_pc3d_upload}">
            <div class="pc3d-upload-info">
              <span class="pc3d-upload-name">{$upload.original_name|escape:'htmlall':'UTF-8'}</span>
              {if $upload.estimated_price}
              <span class="pc3d-upload-price">
                {Context::getContext()->getCurrentLocale()->formatPrice($upload.estimated_price, Context::getContext()->currency->iso_code)}
              </span>
              {/if}
            </div>
            <button type="button" class="btn btn-sm btn-danger pc3d-delete-upload" data-upload-id="{$upload.id_pc3d_upload}">
              <i class="material-icons">delete</i>
            </button>
          </div>
          {/foreach}
        </div>
      </div>
      {/if}
    </div>
  </div>

  {* Info Section *}
  <div class="pc3d-info-section">
    <h4>{l s='How It Works' mod='pcproduct3dcalculator'}</h4>
    <div class="row">
      <div class="col-md-4">
        <div class="pc3d-step">
          <div class="pc3d-step-number">1</div>
          <h5>{l s='Upload Your File' mod='pcproduct3dcalculator'}</h5>
          <p>{l s='Upload your STL or OBJ 3D model file' mod='pcproduct3dcalculator'}</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="pc3d-step">
          <div class="pc3d-step-number">2</div>
          <h5>{l s='Preview & Configure' mod='pcproduct3dcalculator'}</h5>
          <p>{l s='View your model in 3D and select material and infill' mod='pcproduct3dcalculator'}</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="pc3d-step">
          <div class="pc3d-step-number">3</div>
          <h5>{l s='Get Your Quote' mod='pcproduct3dcalculator'}</h5>
          <p>{l s='Receive an instant price estimate' mod='pcproduct3dcalculator'}</p>
        </div>
      </div>
    </div>
  </div>

</div>
{/block}
