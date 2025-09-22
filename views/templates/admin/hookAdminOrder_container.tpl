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

<script type="text/javascript">
  (function () {
    const onlinePaymentsAdminOrderContainer = document.querySelector(`#{$moduleName}-admin-order-container`);
    const onlinePaymentsAjaxTransactionUrl = "{$onlinePaymentsAjaxTransactionUrl|escape:'javascript':'UTF-8'|replace:'&amp;':'&'}";


    onlinePaymentsAdminOrderContainer.addEventListener('click', function (e) {
      if (e.target.matches(`#{$moduleName}-btn-capture`) ||
              e.target.matches(`#{$moduleName}-btn-refund`) ||
              e.target.matches(`#{$moduleName}-btn-cancel`) ||
              e.target.matches(`#{$moduleName}-btn-paybylink`)
      ) {
        e.preventDefault();

        let formToSubmit;
        switch (e.target.id) {
          case `{$moduleName}-btn-capture`:
            formToSubmit = document.querySelector(`#{$moduleName}-capture-form`);
            break;
          case `{$moduleName}-btn-refund`:
            formToSubmit = document.querySelector(`#{$moduleName}-refund-form`);
            break;
          case `{$moduleName}-btn-cancel`:
            formToSubmit = document.querySelector(`#{$moduleName}-cancel-form`);
            break;
          case `{$moduleName}-btn-paybylink`:
            formToSubmit = document.querySelector(`#{$moduleName}-paybylink-form`);
            break;
          default:
            formToSubmit = null;
        }

        if (!formToSubmit) {
          return;
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
        form.append('ajax', true);

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
  })();

</script>

