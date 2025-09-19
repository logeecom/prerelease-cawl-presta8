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
{if isset($transactionData.payment) || isset($paymentLinkData.redirectUrl) || $paymentLinkData.displayButton}
    <div id="{$settingsData.moduleName}-admin-order" class="card">
        <h3 class="card-header">
            <img style="height: 22px;"
                 alt="{$settingsData.brandCode} logo"
                 src="{$settingsData.pathImg|escape:'htmlall':'UTF-8'}{$settingsData.brandCode}.svg"
            />
            {$settingsData.brandName}
        </h3>
        <div class="card-body">
            {if isset($onlinePaymentsAjaxTransactionError)}
                <div class="alert alert-danger">
                    <p class="text-danger">{$onlinePaymentsAjaxTransactionError|escape:'htmlall':'UTF-8'}</p>
                </div>
            {/if}
            {if isset($captureConfirmation) && $captureConfirmation}
                <div class="alert alert-success">
                    <p class="text-success">{l s='Capture requested successfully' mod="{$settingsData.moduleName}"}</p>
                </div>
            {/if}
            {if isset($refundConfirmation) && $refundConfirmation}
                <div class="alert alert-success">
                    <p class="text-success">{l s='Refund requested successfully' mod="{$settingsData.moduleName}"}</p>
                </div>
            {/if}
            {if isset($cancelConfirmation) && $cancelConfirmation}
                <div class="alert alert-success">
                    <p class="text-success">{l s='Cancellation requested successfully' mod="{$settingsData.moduleName}"}</p>
                </div>
            {/if}
            {if isset($paymentLinkData.redirectUrl)}
                <div class="form-group input-group">
                    <input type="text"
                           class="col-md-6 form-control"
                           value="{$paymentLinkData.redirectUrl}"
                           id="{$settingsData.moduleName}PayByLinkGeneratedUrl"
                           name="{$settingsData.moduleName}payByLinkGeneratedUrl"
                           disabled>
                    <div class="input-group-append">
                        <button name="{$settingsData.moduleName}PaymentLinkButton" class="btn btn-sm btn-primary"
                                id="{$settingsData.moduleName}-copy-payment-link">
                            {l s='COPY PAYMENT LINK' mod="{$settingsData.moduleName}"}
                        </button>
                    </div>
                </div>
            {/if}
            {if $paymentLinkData.displayButton && !isset($paymentLinkData.redirectUrl)}
                <div class="row">
                    <div class="col-md-12">
                        <form class="form-horizontal"
                              action="{$settingsData.transactionUrl|escape:'htmlall':'UTF-8'}"
                              name="{$settingsData.moduleName}_paybylink"
                              id="{$settingsData.moduleName}-paybylink-form"
                              style="margin-top: 1.5rem"
                              method="post"
                              enctype="multipart/form-data">
                            <div class="form-group row">
                                <input type="hidden" name="transaction[idOrder]"
                                       value="{$transactionData.orderId|intval}"/>
                                <input type="hidden" name="action" value="paybylink"/>
                                <button id="{$settingsData.moduleName}-btn-paybylink"
                                        class="btn btn-primary btn-lg ml-3">
                                    <i class="material-icons">link</i>
                                    {l s='Generate a payment link ' mod="{$settingsData.moduleName}"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            {/if}
            {if isset($transactionData.payment)}
                <div class="row">
                    <div class="col-md-12">
                        {foreach $transactionData.payments as $payment}
                            <div class="info-block">
                                <div class="row">
                                    <div class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Status' mod="{$settingsData.moduleName}"}</strong></p>
                                        <strong id="">{$payment.status|escape:'htmlall':'UTF-8'}</strong>
                                    </div>
                                    <div class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Transaction number' mod="{$settingsData.moduleName}"}</strong>
                                        </p>
                                        <strong id="">{$payment.id|escape:'htmlall':'UTF-8'}</strong>
                                    </div>
                                    <div id="" class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Total' mod="{$settingsData.moduleName}"}</strong></p>
                                        <strong id="">
                                            {$payment.amount|escape:'htmlall':'UTF-8'}
                                            {$payment.currencyCode|escape:'htmlall':'UTF-8'}
                                        </strong>
                                        {if $payment.hasSurcharge}
                                            <div>
                                                <i>
                                                    {l s='(including' mod="{$settingsData.moduleName}"}
                                                    {$payment.surchargeAmount|escape:'htmlall':'UTF-8'} {$payment.currencyCode|escape:'htmlall':'UTF-8'}
                                                    {l s='surcharge)' mod="{$settingsData.moduleName}"}
                                                </i>
                                            </div>
                                        {/if}
                                    </div>
                                    <div id="" class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Payment Method' mod="{$settingsData.moduleName}"}</strong></p>
                                        <img src="{$settingsData.pathImg|escape:'htmlall':'UTF-8'}payment_products/{$payment.productId|intval}.svg"
                                             alt="{$payment.productName}"
                                             style="height:{($payment.productId|intval == 320) ? 25 : 30}px;"/>
                                    </div>
                                    <div id="" class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Fraud result' mod="{$settingsData.moduleName}"}</strong></p>
                                        <strong id="">
                                            {$payment.fraudResult|escape:'htmlall':'UTF-8'}
                                        </strong>
                                    </div>
                                    <div id="" class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Liability' mod="{$settingsData.moduleName}"}</strong></p>
                                        <strong id="">
                                            {$payment.liability|escape:'htmlall':'UTF-8'}
                                        </strong>
                                    </div>
                                    <div id="" class="col-sm text-center">
                                        <p class="text-muted mb-0">
                                            <strong>{l s='Exemption type' mod="{$settingsData.moduleName}"}</strong></p>
                                        <strong id="">
                                            {$payment.exemptionType|escape:'htmlall':'UTF-8'}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <br>
                        {/foreach}
                    </div>
                </div>
                {if !empty($transactionData.errors)}
                    <div class="alert alert-danger">
                        <ul>
                            {foreach $transactionData.errors as $error}
                                <li>
                                    <b>{l s='Error ID:' mod="{$settingsData.moduleName}"}</b>{$error.id|escape:'htmlall':'UTF-8'}
                                    -
                                    <b>{l s='Code' mod="{$settingsData.moduleName}"}</b> {$error.code|escape:'htmlall':'UTF-8'}
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}
                {if $transactionData.psOrderAmountMatch === false}
                    <div class="alert alert-warning">
                        <p>
                            {l s='Warning: This order may not have been fully paid!' mod="{$settingsData.moduleName}"}
                        </p>
                        <p>
                            {l s='Please review the amounts in the section above and in the "Products" section in this page.' mod="{$settingsData.moduleName}"}
                            <br>
                        </p>
                    </div>
                {/if}
                <p></p>
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        {if $transactionData.payment.hasSurcharge}
                                            <h4>{l s='Surcharge details' mod="{$settingsData.moduleName}"}</h4>
                                            <div class="row mb-1">
                                                <div class="col-6 text-right">{l s='Total amount without surcharge' mod="{$settingsData.moduleName}"}</div>
                                                <div class="col-6">
                                                    {$transactionData.payment.amountWithoutSurcharge|escape:'htmlall':'UTF-8'}
                                                    {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                                </div>
                                            </div>
                                            <div class="row mb-1">
                                                <div class="col-6 text-right">{l s='Surcharge amount' mod="{$settingsData.moduleName}"}</div>
                                                <div class="col-6">
                                                    {$transactionData.payment.surchargeAmount|escape:'htmlall':'UTF-8'}
                                                    {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                                </div>
                                            </div>
                                            <div class="row mb-1">
                                                <div class="col-6 text-right">{l s='Total amount with surcharge' mod="{$settingsData.moduleName}"}</div>
                                                <div class="col-6">
                                                    {$transactionData.payment.amount|escape:'htmlall':'UTF-8'}
                                                    {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                                </div>
                                            </div>
                                            <hr>
                                        {/if}
                                        <h4>{l s='Capture' mod="{$settingsData.moduleName}"}</h4>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount captured' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.captures.totalCaptured|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount pending capture' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.captures.totalPendingCapture|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount that can be captured' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.captures.capturableAmount|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        {if $transactionData.actions.isAuthorized && $transactionData.captures.capturableAmount > 0}
                                            <form class="form-horizontal"
                                                  action="{$settingsData.transactionUrl|escape:'htmlall':'UTF-8'}"
                                                  name="{$settingsData.moduleName}_capture"
                                                  id="{$settingsData.moduleName}-capture-form"
                                                  style="margin-top: 1.875rem"
                                                  method="post"
                                                  enctype="multipart/form-data">
                                                <div class="form-group row">
                                                    <div class="col-sm">
                                                        <div class="input-group money-type">
                                                            <input type="text"
                                                                   id=""
                                                                   name="transaction[amountToCapture]"
                                                                   class="form-control"
                                                                   onchange="this.value = parseFloat(this.value.replace(/,/g, '.')) || 0"
                                                                   value="{$transactionData.captures.capturableAmount|escape:'htmlall':'UTF-8'}">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">{$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}</span>
                                                            </div>
                                                            <button id="{$settingsData.moduleName}-btn-capture"
                                                                    class="btn btn-primary btn-sm ml-2">
                                                                {l s='Capture' mod="{$settingsData.moduleName}"}
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="transaction[id]"
                                                               value="{$transactionData.payment.id|escape:'htmlall':'UTF-8'}"/>
                                                        <input type="hidden" name="transaction[idOrder]"
                                                               value="{$transactionData.orderId|intval}"/>
                                                        <input type="hidden" name="transaction[currencyCode]"
                                                               value="{$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}"/>
                                                        <input type="hidden" name="action" value="capture"/>
                                                    </div>
                                                </div>
                                            </form>
                                        {/if}
                                        <hr>
                                        <h4>{l s='Cancel transaction' mod="{$settingsData.moduleName}"}</h4>
                                        {if $transactionData.actions.isCancellable && $transactionData.cancels.cancellableAmount > 0}
                                            <div class="alert alert-warning">
                                                <p class="alert-text">{l s='Be careful, this action cannot be reverted' mod="{$settingsData.moduleName}"}</p>
                                            </div>
                                        {/if}
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount cancelled' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.cancels.totalCancelled|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount pending cancel' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.cancels.totalPendingCancel|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount that can be cancelled' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.cancels.cancellableAmount|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        {if $transactionData.actions.isCancellable && $transactionData.cancels.cancellableAmount > 0}
                                            <form class="form-horizontal"
                                                  action="{$settingsData.transactionUrl|escape:'htmlall':'UTF-8'}"
                                                  name="{$settingsData.moduleName}_cancel"
                                                  id="{$settingsData.moduleName}-cancel-form"
                                                  style="margin-top: 1.875rem"
                                                  method="post"
                                                  enctype="multipart/form-data">
                                                <div class="form-group row">
                                                    <div class="col-sm">
                                                        <div class="input-group money-type">
                                                            <input type="text"
                                                                   id=""
                                                                   name="transaction[amountToCancel]"
                                                                   class="form-control"
                                                                   onchange="this.value = parseFloat(this.value.replace(/,/g, '.')) || 0"
                                                                   value="{$transactionData.cancels.cancellableAmount|escape:'htmlall':'UTF-8'}">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">{$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}</span>
                                                            </div>
                                                            <button id="{$settingsData.moduleName}-btn-cancel"
                                                                    class="btn btn-danger btn-sm ml-2">
                                                                {l s='Cancel' mod="{$settingsData.moduleName}"}
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="transaction[id]"
                                                               value="{$transactionData.payment.id|escape:'htmlall':'UTF-8'}"/>
                                                        <input type="hidden" name="transaction[idOrder]"
                                                               value="{$transactionData.orderId|intval}"/>
                                                        <input type="hidden" name="transaction[currencyCode]"
                                                               value="{$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}"/>
                                                        <input type="hidden" name="action" value="cancel"/>
                                                    </div>
                                                </div>
                                            </form>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h4>{l s='Refund' mod="{$settingsData.moduleName}"}</h4>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount refunded' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.refunds.totalRefunded|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount pending refund' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.refunds.totalPendingRefund|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        <div class="row mb-1">
                                            <div class="col-6 text-right">{l s='Amount that can be refunded' mod="{$settingsData.moduleName}"}</div>
                                            <div class="col-6">
                                                {$transactionData.refunds.refundableAmount|escape:'htmlall':'UTF-8'}
                                                {$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}
                                            </div>
                                        </div>
                                        {if $transactionData.captures.capturableAmount > 0 && !$transactionData.actions.isRefundable}
                                            <hr>
                                            <div class="alert alert-info">
                                                <p>
                                                    {l s='You can make refunds if the initial transaction is fully captured or partially cancelled' mod="{$settingsData.moduleName}"}
                                                </p>
                                            </div>
                                        {/if}
                                        {if $transactionData.actions.isRefundable && $transactionData.refunds.refundableAmount > 0}
                                            <hr>
                                            <form class="form-horizontal"
                                                  action="{$settingsData.transactionUrl|escape:'htmlall':'UTF-8'}"
                                                  name="{$settingsData.moduleName}_refund"
                                                  id="{$settingsData.moduleName}-refund-form"
                                                  method="post"
                                                  enctype="multipart/form-data">
                                                <div class="form-group row">
                                                    <div class="col-sm">
                                                        <div class="input-group money-type">
                                                            <input type="text"
                                                                   id=""
                                                                   name="transaction[amountToRefund]"
                                                                   class="form-control"
                                                                   onchange="this.value = parseFloat(this.value.replace(/,/g, '.')) || 0"
                                                                   value="{$transactionData.refunds.refundableAmount|escape:'htmlall':'UTF-8'}">
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">{$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}</span>
                                                            </div>
                                                            <button id="{$settingsData.moduleName}-btn-refund"
                                                                    class="btn btn-primary btn-sm ml-2">
                                                                {l s='Make refund' mod="{$settingsData.moduleName}"}
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="transaction[id]"
                                                               value="{$transactionData.payment.id|escape:'htmlall':'UTF-8'}"/>
                                                        <input type="hidden" name="transaction[idOrder]"
                                                               value="{$transactionData.orderId|intval}"/>
                                                        <input type="hidden" name="transaction[currencyCode]"
                                                               value="{$transactionData.payment.currencyCode|escape:'htmlall':'UTF-8'}"/>
                                                        <input type="hidden" name="action" value="refund"/>
                                                    </div>
                                                </div>
                                            </form>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}
            {if !isset($transactionData.payment) &&
            !isset($paymentLinkData.redirectUrl) &&
            !$paymentLinkData.displayButton
            && !empty($errorMessages)
            }
                <div class="alert alert-danger">
                    <p>{l s='Transaction data does not exist and payment link is not available. More details:' mod="{$settingsData.moduleName}"}</p>
                    <ul>
                        {foreach $errorMessages as $message}
                            <li>{$message}</li>
                        {/foreach}
                    </ul>
                </div>
            {/if}
        </div>
    </div>
{/if}