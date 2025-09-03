/*
 * 2021 Online Payments
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 * @author    PrestaShop partner
 * @copyright 2021 Online Payments
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */

/**
 * @param {{
 *          module: string,
 *          redirectController: string,
 *          returnMac: string,
 *          hostedCheckoutId: string,
 *          paymentId: string,
 *          customerToken: string}} config
 */
function onlinePaymentsWaitingInit(config) {
  async function onlinePaymentsRedirect(getCall) {
    const controller = config.redirectController.replace(/\amp;/g, '');

    return new Promise(function (resolve, reject) {
      const form = new FormData();

      form.append('ajax', true);
      form.append('RETURNMAC', config.returnMac);
      form.append('hostedCheckoutId', config.hostedCheckoutId);
      form.append('paymentId', config.paymentId);
      form.append('token', config.customerToken);
      form.append('getCall', getCall);

      console.debug(getCall);
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

  function setTimer(callback, delay, iterations) {
    let i = 0;
    const triggerIterations = [7, 17, 37, 57, 77, 117];

    let intervalID = window.setInterval(function () {

      if (i < 7) {
        callback(0, function () {
          window.clearInterval(intervalID);

        });
      } else {
        if (i === 7) {
          document.getElementById(`js-${config.module}-timeout-message`).style.display = 'block';
        }
        if (triggerIterations.includes(i) || ((i - 417) % 300) === 0) {
          // Trigger GET call, after 30 seconds, 1 minutes, 2 minutes, 3 minutes, then every 5 minutes
          // 1 iteration = 3 seconds
          callback(1, function () {
            window.clearInterval(intervalID);

          });
        }
        if (((i - 417) % 300) === 1) {
          // We make another call at iteration + 1 in case of the order has been validated
          callback(0, function () {
            window.clearInterval(intervalID);

          });
        }
      }
      i++;
      if (i === iterations) {
        window.clearInterval(intervalID);
      }

    }, delay);
  }

  setTimer(function (getCall, resolve) {
    onlinePaymentsRedirect(getCall).then((result) => {
      if (result.redirectUrl) {
        window.top.location.href = result.redirectUrl;
        resolve();
      }
    }).catch(() => {
    });
  }, 3000, 3600);
}
