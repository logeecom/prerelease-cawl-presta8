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

<div id="{$moduleName}-admin-order-container">
  {$html}
</div>

{literal}
<script type="text/javascript">

  {/literal}
  const onlinePaymentsModule = "{$moduleName}";
  {literal}

  const onlinePaymentsAdminOrderContainer = document.querySelector(`#${onlinePaymentsModule}-admin-order-container`);
  const refundForm = document.querySelector(`#${onlinePaymentsModule}-refund-form`);

  onlinePaymentsAdminOrderContainer.addEventListener('click', function (e) {
    if (e.target.matches(`#${onlinePaymentsModule}-btn-capture`) ||
            e.target.matches(`#${onlinePaymentsModule}-btn-refund`) ||
            e.target.matches(`#${onlinePaymentsModule}-btn-cancel`) ||
            e.target.matches(`#${onlinePaymentsModule}-btn-paybylink`)
    ) {
      e.preventDefault();

      var formToSubmit;
      if (e.target.matches(`#${onlinePaymentsModule}-btn-capture`)) {
        if (!window.confirm(alertCapture)) {
          return false;
        }

        formToSubmit = document.querySelector(`#${onlinePaymentsModule}-capture-form`);
      } else if (e.target.matches(`#${onlinePaymentsModule}-btn-refund`)) {
        if (!window.confirm(alertRefund)) {
          return false;
        }

        formToSubmit = document.querySelector(`#${onlinePaymentsModule}-refund-form`);
      } else if (e.target.matches(`#${onlinePaymentsModule}-btn-cancel`)) {
        if (!window.confirm(alertCancel)) {
          return false;
        }

        formToSubmit = document.querySelector(`#${onlinePaymentsModule}-cancel-form`);
      } else {
        formToSubmit = document.querySelector(`#${onlinePaymentsModule}-paybylink-form`);
      }

      const submitBtn = formToSubmit.querySelector('button');

      submitBtn.disabled = true;
      onlinePaymentsAdminOrderContainer.style.opacity = 0.6;
      onlinePaymentsPostTransaction(formToSubmit).then((result) => {
        onlinePaymentsAdminOrderContainer.innerHTML = result.result_html;
      }).catch(() => {
      }).finally(() => {
        onlinePaymentsAdminOrderContainer.style.opacity = 1;
        submitBtn.disabled = false;
      });
    }
  }, false);

  async function onlinePaymentsPostTransaction(formSent) {
    const controller = onlinePaymentsAjaxTransactionUrl.replace(/\amp;/g, '');

    return new Promise(function (resolve, reject) {
      const form = new FormData(formSent);

      fetch(controller, {
        body: form,
        method: 'post',
      }).then((response) => {
        resolve(response.json());
      }).catch((err) => {
        reject(err);
      });
    });
  }
</script>
{/literal}
