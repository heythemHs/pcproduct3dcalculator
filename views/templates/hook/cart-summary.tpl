{**
 * Shopping Cart 3D Uploads Summary
 *}

{if $pc3d_uploads|@count > 0}
<div class="pc3d-cart-summary card">
  <div class="card-header">
    <h3 class="h4 mb-0">
      <i class="material-icons">print</i>
      {l s='3D Print Uploads' mod='pcproduct3dcalculator'}
    </h3>
  </div>
  <div class="card-body">
    <table class="table table-bordered pc3d-cart-table">
      <thead>
        <tr>
          <th>{l s='File' mod='pcproduct3dcalculator'}</th>
          <th>{l s='Material' mod='pcproduct3dcalculator'}</th>
          <th>{l s='Infill' mod='pcproduct3dcalculator'}</th>
          <th class="text-right">{l s='Price' mod='pcproduct3dcalculator'}</th>
          <th class="text-center">{l s='Actions' mod='pcproduct3dcalculator'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$pc3d_uploads item=upload}
        <tr class="pc3d-upload-row" data-upload-id="{$upload.id_pc3d_upload}">
          <td>
            <span class="pc3d-file-icon">
              {assign var="ext" value=$upload.original_name|pathinfo:$smarty.const.PATHINFO_EXTENSION|upper}
              {if $ext == 'STL'}
                <i class="material-icons">view_in_ar</i>
              {else}
                <i class="material-icons">insert_drive_file</i>
              {/if}
            </span>
            <span class="pc3d-file-name">{$upload.original_name|escape:'htmlall':'UTF-8'}</span>
            {if $upload.volume_cm3}
              <small class="text-muted d-block">
                {$upload.volume_cm3|number_format:2} cm³
              </small>
            {/if}
          </td>
          <td>
            {if $upload.material_name}
              {if $upload.material_color}
                <span class="pc3d-material-color" style="background-color: {$upload.material_color|escape:'htmlall':'UTF-8'}"></span>
              {/if}
              {$upload.material_name|escape:'htmlall':'UTF-8'}
            {else}
              <span class="text-muted">{l s='Not selected' mod='pcproduct3dcalculator'}</span>
            {/if}
          </td>
          <td class="text-center">
            {$upload.infill_percent|number_format:0}%
          </td>
          <td class="text-right pc3d-price">
            {if $upload.estimated_price}
              {Tools::displayPrice($upload.estimated_price, $pc3d_currency)}
            {else}
              <span class="text-warning">{l s='Pending' mod='pcproduct3dcalculator'}</span>
            {/if}
          </td>
          <td class="text-center">
            <button type="button"
                    class="btn btn-sm btn-outline-danger pc3d-delete-upload"
                    data-upload-id="{$upload.id_pc3d_upload}"
                    title="{l s='Remove' mod='pcproduct3dcalculator'}">
              <i class="material-icons">close</i>
            </button>
          </td>
        </tr>
        {/foreach}
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="text-right">
            <strong>{l s='3D Print Total:' mod='pcproduct3dcalculator'}</strong>
          </td>
          <td class="text-right">
            <strong>
              {assign var="total" value=0}
              {foreach from=$pc3d_uploads item=upload}
                {if $upload.estimated_price}
                  {$total = $total + $upload.estimated_price}
                {/if}
              {/foreach}
              {Tools::displayPrice($total, $pc3d_currency)}
            </strong>
          </td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
{/if}
