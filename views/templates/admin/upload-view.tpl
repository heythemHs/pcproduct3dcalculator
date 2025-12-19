{**
 * Admin Upload View Template
 *}

<div class="panel">
  <div class="panel-heading">
    <i class="icon-file"></i>
    {l s='Upload Details' mod='pcproduct3dcalculator'}
    <span class="badge">{$upload->original_name|escape:'htmlall':'UTF-8'}</span>
  </div>

  <div class="panel-body">
    <div class="row">
      {* Left Column - File Info *}
      <div class="col-lg-6">
        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Upload ID' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">{$upload->id}</p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Original Filename' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {$upload->original_name|escape:'htmlall':'UTF-8'}
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Stored Filename' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              <code>{$upload->filename|escape:'htmlall':'UTF-8'}</code>
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='File Size' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">{$file_size_formatted}</p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='File Exists' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {if $file_exists}
                <span class="label label-success">{l s='Yes' mod='pcproduct3dcalculator'}</span>
              {else}
                <span class="label label-danger">{l s='No' mod='pcproduct3dcalculator'}</span>
              {/if}
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Upload Date' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">{$upload->date_add}</p>
          </div>
        </div>
      </div>

      {* Right Column - Calculation Details *}
      <div class="col-lg-6">
        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Volume' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {if $upload->volume_cm3}
                {$upload->volume_cm3|number_format:4} cm³
              {else}
                <span class="text-muted">-</span>
              {/if}
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Weight' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {if $upload->weight_grams}
                {$upload->weight_grams|number_format:2} g
              {else}
                <span class="text-muted">-</span>
              {/if}
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Material' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {if $material}
                {$material.name|escape:'htmlall':'UTF-8'}
                <small class="text-muted">
                  ({$material.density} g/cm³ @ {Tools::displayPrice($material.price_per_gram)}/g)
                </small>
              {else}
                <span class="text-muted">{l s='Not selected' mod='pcproduct3dcalculator'}</span>
              {/if}
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Infill' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">{$upload->infill_percent|number_format:0}%</p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Estimated Price' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {if $upload->estimated_price}
                <span class="badge badge-success" style="font-size: 1.2em;">
                  {Tools::displayPrice($upload->estimated_price)}
                </span>
              {else}
                <span class="text-muted">-</span>
              {/if}
            </p>
          </div>
        </div>

        <div class="form-group">
          <label class="control-label col-lg-4">{l s='Status' mod='pcproduct3dcalculator'}</label>
          <div class="col-lg-8">
            <p class="form-control-static">
              {assign var="status" value=$upload->status}
              {if $status == 'pending'}
                <span class="label label-warning">{l s='Pending' mod='pcproduct3dcalculator'}</span>
              {elseif $status == 'calculated'}
                <span class="label label-info">{l s='Calculated' mod='pcproduct3dcalculator'}</span>
              {elseif $status == 'in_cart'}
                <span class="label label-primary">{l s='In Cart' mod='pcproduct3dcalculator'}</span>
              {elseif $status == 'ordered'}
                <span class="label label-success">{l s='Ordered' mod='pcproduct3dcalculator'}</span>
              {elseif $status == 'processing'}
                <span class="label label-info">{l s='Processing' mod='pcproduct3dcalculator'}</span>
              {elseif $status == 'completed'}
                <span class="label label-success">{l s='Completed' mod='pcproduct3dcalculator'}</span>
              {elseif $status == 'cancelled'}
                <span class="label label-danger">{l s='Cancelled' mod='pcproduct3dcalculator'}</span>
              {else}
                <span class="label label-default">{$status|escape:'htmlall':'UTF-8'}</span>
              {/if}
            </p>
          </div>
        </div>
      </div>
    </div>

    {* Associations *}
    <hr>
    <div class="row">
      <div class="col-lg-12">
        <h4>{l s='Associations' mod='pcproduct3dcalculator'}</h4>
      </div>

      <div class="col-lg-4">
        <div class="form-group">
          <label class="control-label">{l s='Cart ID' mod='pcproduct3dcalculator'}</label>
          <p class="form-control-static">
            {if $upload->id_cart}
              #{$upload->id_cart}
            {else}
              <span class="text-muted">-</span>
            {/if}
          </p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="form-group">
          <label class="control-label">{l s='Order ID' mod='pcproduct3dcalculator'}</label>
          <p class="form-control-static">
            {if $upload->id_order}
              <a href="{$link->getAdminLink('AdminOrders')}&id_order={$upload->id_order}&vieworder" target="_blank">
                #{$upload->id_order} <i class="icon-external-link"></i>
              </a>
            {else}
              <span class="text-muted">-</span>
            {/if}
          </p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="form-group">
          <label class="control-label">{l s='Customer ID' mod='pcproduct3dcalculator'}</label>
          <p class="form-control-static">
            {if $upload->id_customer}
              <a href="{$link->getAdminLink('AdminCustomers')}&id_customer={$upload->id_customer}&viewcustomer" target="_blank">
                #{$upload->id_customer} <i class="icon-external-link"></i>
              </a>
            {else}
              <span class="text-muted">{l s='Guest' mod='pcproduct3dcalculator'}</span>
            {/if}
          </p>
        </div>
      </div>
    </div>

    {* Notes *}
    {if $upload->notes}
    <hr>
    <div class="row">
      <div class="col-lg-12">
        <h4>{l s='Notes' mod='pcproduct3dcalculator'}</h4>
        <div class="well">{$upload->notes|escape:'htmlall':'UTF-8'|nl2br}</div>
      </div>
    </div>
    {/if}
  </div>

  <div class="panel-footer">
    <a href="{$back_url}" class="btn btn-default">
      <i class="process-icon-back"></i>
      {l s='Back to list' mod='pcproduct3dcalculator'}
    </a>

    {if $file_exists}
    <a href="{$link->getAdminLink('AdminPc3dUploads')}&ajax=1&action=downloadFile&id_pc3d_upload={$upload->id}"
       class="btn btn-default pull-right">
      <i class="icon-download"></i>
      {l s='Download File' mod='pcproduct3dcalculator'}
    </a>
    {/if}
  </div>
</div>
