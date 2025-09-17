{**
 * 2021 Crédit Agricole
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 * @author    PrestaShop / PrestaShop partner
 * @copyright 2020-2021 Crédit Agricole
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 *}

{if $tokenSurcharge|escape:'htmlall':'UTF-8'}
  <div class="alert alert-info">
    <p>{l s='Please note that a surcharge will be applied to the total amount:' mod=$module}</p>
    <ul>
      <li>
        {l s='Initial total:' mod=$module}
        {$tokenSurcharge.amountWithoutSurcharge|escape:'htmlall':'UTF-8'} {$tokenSurcharge.currencyIso}
      </li>
      <li>
        {l s='Surcharge amount:' mod=$module}
        {$tokenSurcharge.surchargeAmount|escape:'htmlall':'UTF-8'} {$tokenSurcharge.currencyIso}
      </li>
      <li>
        <b>
          {l s='Total amount with surcharge:' mod=$module}
          {$tokenSurcharge.amountWithSurcharge|escape:'htmlall':'UTF-8'} {$tokenSurcharge.currencyIso}
        </b>
      </li>
    </ul>
  </div>
{/if}

<div class="js-{$module}-1click-container-{$tokenId|escape:'htmlall':'UTF-8'}">
  <div id="js-{$module}-1click-{$tokenId|escape:'htmlall':'UTF-8'}" class="js-{$module}-htp {$module}-htp online-payments-htp"></div>

  <div class="js-{$module}-generic-error alert alert-danger" style="display: none">
    {l s='An error occurred while processing the payment.' mod=$module}
    <a href="javascript:window.location.reload()">{l s='Please click here' mod=$module}</a>
    {l s='to refresh this page or contact our Customer Service' mod=$module}
  </div>
  <div class="js-{$module}-error alert alert-danger" style="display: none">
    <span></span>
    <a href="javascript:window.location.reload()">{l s='Please click here' mod=$module}</a>
    {l s='to refresh this page or contact our Customer Service' mod=$module}
  </div>
</div>

<script type="text/javascript">
  (function () {
    hostedTokenizationObj = new htpPrototype(document, '{$module|escape:'javascript':'UTF-8'}');

    hostedTokenizationObj.elems = {
      iframeContainer: document.querySelector(".js-{$module|escape:'javascript':'UTF-8'}-1click-container-{$tokenId|escape:'javascript':'UTF-8'}"),
      payBtnId: "js-{$module|escape:'javascript':'UTF-8'}-token-btn-submit-{$tokenId|escape:'javascript':'UTF-8'}",
    };
    hostedTokenizationObj.urls = {
      htp: "{$hostedTokenizationPageUrl|escape:'javascript':'UTF-8'|replace:'&amp;':'&'}",
      paymentController: "{$paymentControllerUrl|escape:'javascript':'UTF-8'|replace:'&amp;':'&'}",
    };
    hostedTokenizationObj.dynamicSurcharge = false;
    hostedTokenizationObj.cartDetails = {
      totalCents: "{$totalCartCents|intval}",
      currencyCode: "{$cartCurrencyCode|escape:'javascript':'UTF-8'}",
      customerToken: "{$customerToken|escape:'javascript':'UTF-8'}",
      cardToken: "{$cardToken|escape:'javascript':'UTF-8'}",
    };
    hostedTokenizationObj.init();
  })()
</script>
