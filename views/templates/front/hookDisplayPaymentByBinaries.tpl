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

<!-- Single HTP -->
{if isset($displayHTP) && $displayHTP}
<div class="js-payment-binary js-payment-{$module}-htp {$module}-htp-btn online-payments-htp-btn">
  <button id="js-{$module}-btn-submit" type="submit" disabled="disabled" class="btn btn-primary center-block">
    {l s='Place order' mod=$module}
  </button>
</div>
{/if}
<!-- /Single HTP -->

{if isset($tokenHTP)}
  {foreach $tokenHTP as $htp}
    <!-- Token HTP -->
    <div class="js-payment-binary js-payment-{$module}-token-htp-{$htp.id} {$module}-token-htp-btn online-payments-token-htp-btn">
      <button id="js-{$module}-token-btn-submit-{$htp.id}" type="submit" disabled="disabled" class="btn btn-primary center-block">
        {l s='Place order' mod=$module}
      </button>
    </div>
    <!-- /Token HTP -->
  {/foreach}
{/if}
