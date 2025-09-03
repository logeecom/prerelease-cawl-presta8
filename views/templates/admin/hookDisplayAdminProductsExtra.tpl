{**
 * 2021 Online Payments
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 * @author    PrestaShop / PrestaShop partner
 * @copyright 2020-2021 Online Payments
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 *}

<div class="row">
  <div class="col-md-12">
    <h2>{l s='Gift card specific configuration' mod={$module}}</h2>
    <p class="subtitle">{$title}</p>
  </div>
</div>
<div class="row form-group">
  <div class="col-md-8">
    <label for="{$module}[product_type]" class="form-control-label">{l s='Product type' mod={$module}}</label>
    <select id="{$module}[product_type]"
            name="{$module}[product_type]"
            class="custom-select custom-select">
      <option value="">{l s='None' mod=$module}</option>
      {foreach $availableProductTypes as $productType => $label}
        <option {if $selectedProductType == $productType}selected="selected"{/if} value="{$productType|escape:'htmlall':'UTF-8'}">{$label}</option>
      {/foreach}
    </select>
  </div>
</div>
