{if $pc3d_uploads|@count}
<div class="pc3d-cart-summary">
  <h3>{l s='3D Print uploads' mod='pcproduct3dcalculator'}</h3>
  <table class="table">
    <thead>
      <tr>
        <th>{l s='File' mod='pcproduct3dcalculator'}</th>
        <th class="text-right">{l s='Estimated price' mod='pcproduct3dcalculator'}</th>
      </tr>
    </thead>
    <tbody>
      {foreach from=$pc3d_uploads item=upload}
        <tr>
          <td>{$upload.original_name}</td>
          <td class="text-right">
            {if $upload.estimated_price}
              {$upload.estimated_price} {Context::getContext()->currency->sign|escape:'htmlall':'UTF-8'}
            {else}
              {l s='Pending calculation' mod='pcproduct3dcalculator'}
            {/if}
          </td>
        </tr>
      {/foreach}
    </tbody>
  </table>
</div>
{/if}
