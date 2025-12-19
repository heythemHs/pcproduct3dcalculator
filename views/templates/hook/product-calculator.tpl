{**
 * Product Page 3D Calculator Hook Template
 *}

<div class="pc3d-product-calculator" id="pc3d-calculator">
  <input type="hidden" name="pc3d_product_id" value="{$pc3d_product_id}">

  {* Alert Messages *}
  <div class="pc3d-alert-success alert alert-success" style="display:none;"></div>
  <div class="pc3d-alert-error alert alert-danger" style="display:none;"></div>

  {* Loading Overlay *}
  <div class="pc3d-loading" style="display:none;">
    <div class="pc3d-loading-spinner"></div>
    <span class="pc3d-loading-text">{l s='Processing...' mod='pcproduct3dcalculator'}</span>
  </div>

  <div class="pc3d-section-header">
    <h4>
      <i class="material-icons">print</i>
      {l s='3D Print Calculator' mod='pcproduct3dcalculator'}
    </h4>
  </div>

  <form class="pc3d-form" method="post" enctype="multipart/form-data">
    {* Compact Drop Zone *}
    <div class="pc3d-drop-zone pc3d-drop-zone--compact">
      <input type="file" name="pc3d_file" class="pc3d-file-input" accept=".stl,.obj" hidden>
      <div class="pc3d-drop-zone-content">
        <i class="material-icons">cloud_upload</i>
        <span class="pc3d-drop-zone-text">{l s='Upload STL/OBJ file' mod='pcproduct3dcalculator'}</span>
        <span class="pc3d-file-name" style="display:none;"></span>
      </div>
      <small class="pc3d-file-info">{l s='Max:' mod='pcproduct3dcalculator'} {$pc3d_max_file_size}MB</small>
    </div>

    <div class="pc3d-options-row">
      {* Material Selection *}
      <div class="pc3d-option">
        <label for="pc3d-material-product">{l s='Material' mod='pcproduct3dcalculator'}</label>
        <select id="pc3d-material-product" name="pc3d_material" class="form-control pc3d-material-select">
          <option value="">{l s='Select...' mod='pcproduct3dcalculator'}</option>
          {foreach from=$pc3d_materials item=material}
            <option value="{$material.id_pc3d_material}"
                    {if $material.color}style="border-left: 4px solid {$material.color|escape:'htmlall':'UTF-8'}"{/if}>
              {$material.name|escape:'htmlall':'UTF-8'}
            </option>
          {/foreach}
        </select>
      </div>

      {* Infill Selection *}
      <div class="pc3d-option">
        <label for="pc3d-infill-product">
          {l s='Infill' mod='pcproduct3dcalculator'}
          <span class="pc3d-infill-value">{$pc3d_default_infill}%</span>
        </label>
        <input type="range"
               id="pc3d-infill-product"
               name="pc3d_infill"
               class="pc3d-infill-input"
               min="0"
               max="100"
               step="5"
               value="{$pc3d_default_infill}">
      </div>
    </div>
  </form>

  {* Results *}
  <div class="pc3d-results pc3d-results--compact" style="display:none;">
    <div class="pc3d-results-grid">
      {if $pc3d_show_volume}
      <div class="pc3d-result-item">
        <span class="pc3d-result-label">{l s='Volume' mod='pcproduct3dcalculator'}</span>
        <span class="pc3d-result-value pc3d-result-volume">-</span>
      </div>
      {/if}

      {if $pc3d_show_weight}
      <div class="pc3d-result-item">
        <span class="pc3d-result-label">{l s='Weight' mod='pcproduct3dcalculator'}</span>
        <span class="pc3d-result-value pc3d-result-weight">-</span>
      </div>
      {/if}

      <div class="pc3d-result-item pc3d-result-item--price">
        <span class="pc3d-result-label">{l s='Estimated Price' mod='pcproduct3dcalculator'}</span>
        <span class="pc3d-result-value pc3d-result-price">-</span>
      </div>
    </div>

    <div class="pc3d-actions">
      <button type="button" class="btn btn-primary pc3d-add-to-cart" disabled>
        {l s='Add 3D Print to Cart' mod='pcproduct3dcalculator'}
      </button>
    </div>
  </div>
</div>
