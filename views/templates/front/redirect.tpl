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

{extends file='page.tpl'}

{block name='page_content_container'}
  <div id="js-{$module}-loader">
    <h1>{l s='Please wait while we are processing your payment' mod=$module}</h1>
    <img src="{$img_path}icons/loader.svg" title="Loading..." alt="Loading..." />
  </div>
  <div id="js-{$module}-timeout-message" style="display: none;">
    <div class="alert alert-warning">
      <p>{l s='The transaction has not been confirmed yet.' mod=$module}</p>
      <p>
        {l s='We suggest you contact our customer service using this link:' mod=$module}
        <a title="{l s='Contact-us' mod=$module}" href="{$link->getPageLink('contact', true)}">
          {$link->getPageLink('contact', true)}
        </a>
      </p>
      {if $hostedCheckoutId || $paymentId}
        <p>
          {l s='Please also provide us these transactions details:' mod=$module}<br>
          {if $paymentId}
            <b>{l s='Payment ID:' mod=$module}</b> {$paymentId|escape:'htmlall':'UTF-8'}
          {/if}
          {if $hostedCheckoutId}
            <b>{l s='Checkout ID:' mod=$module}</b> {$hostedCheckoutId|escape:'htmlall':'UTF-8'}
          {/if}
        </p>
      {/if}
    </div>
  </div>
{/block}

{block name="javascript_bottom"}
  {$smarty.block.parent}
  <script>
    onlinePaymentsWaitingInit({
      'module': "{$module|escape:'javascript':'UTF-8'}",
      'redirectController': "{$redirectController|escape:'javascript':'UTF-8'|replace:'&amp;':'&' nofilter}",
      'returnMac': "{$returnMac|escape:'javascript':'UTF-8'}",
      'hostedCheckoutId': "{$hostedCheckoutId|escape:'javascript':'UTF-8'}",
      'paymentId': "{$paymentId|escape:'javascript':'UTF-8'}",
      'customerToken': "{$customerToken|escape:'javascript':'UTF-8'}",
    });
  </script>
{/block}
