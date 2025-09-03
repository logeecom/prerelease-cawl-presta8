<!DOCTYPE html>
<!--suppress HtmlUnknownAnchorTarget, HtmlUnknownTarget -->
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Online Payments admin FE</title>
</head>
<body>
<div id="op-page" class="op-page">
    <main>
        <div class="opp-content-holder">
            <header id="op-main-header">
            </header>
            <main id="op-main-page-holder"></main>
            <div id="op-footer"></div>
        </div>
    </main>
    <div class="op-page-loader ops--hidden" id="op-spinner">
        <div class="op-loader opt--large">
            <span class="opp-spinner"></span>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!{$brand.code}) {
            {$brand.code} = {};
        }

        let OnlinePaymentsFE = {$brand.code}

        OnlinePaymentsFE.translations = {
            default: {$translations.default|json_encode},
            current: {$translations.current|json_encode}
        };

        OnlinePaymentsFE.baseImgUrl = "/modules/{$module}/views/assets/images";

        OnlinePaymentsFE.brand = {$brand|json_encode};
        OnlinePaymentsFE.utilities.showLoader();

        const pageConfiguration = {
            connection: {
                getSettingsUrl: '{$urls.connection.getSettingsUrl}',
                submitUrl: '{$urls.connection.submitUrl}',
                disconnectUrl: '',
                webhooksUrl: '{$urls.connection.webhooksUrl}'
            },
            payments: {
                getAvailablePaymentsUrl: '{$urls.payments.getAvailablePaymentsUrl}',
                enableMethodUrl: '{$urls.payments.enableMethodUrl}',
                saveMethodConfigurationUrl: '{$urls.payments.saveMethodConfigurationUrl}',
                getMethodConfigurationUrl: '{$urls.payments.getMethodConfigurationUrl}',
                paymentMethodLogoUrl:  window.location.protocol + '//' + window.location.host +
                    '/modules/{$module}/views/assets/images/payment_products/',
                getLanguagesUrl: '{$urls.payments.getLanguagesUrl}'
            },
            settings: {
                getGeneralSettingsUrl: '{$urls.settings.getGeneralSettingsUrl}',
                getPaymentStatusesUrl: '{$urls.settings.getPaymentStatusesUrl}',
                saveConnectionUrl: '{$urls.settings.saveConnectionUrl}',
                saveCardsSettingsUrl: '{$urls.settings.saveCardsSettingsUrl}',
                savePaymentSettingsUrl: '{$urls.settings.savePaymentSettingsUrl}',
                saveLogSettingsUrl: '{$urls.settings.saveLogSettingsUrl}',
                savePayByLinkSettingsUrl: '{$urls.settings.savePayByLinkSettingsUrl}',
                webhooksUrl: '{$urls.settings.webhooksUrl}',
                disconnectUrl: '{$urls.settings.disconnectUrl}'
            },
            monitoring: {
                getMonitoringLogsUrl: '{$urls.monitoring.getMonitoringLogsUrl}',
                getWebhookLogsUrl: '{$urls.monitoring.getWebhookLogsUrl}',
                downloadMonitoringLogsUrl: '{$urls.monitoring.downloadMonitoringLogsUrl}',
                downloadWebhookLogsUrl: '{$urls.monitoring.downloadWebhookLogsUrl}',
                page: 'webhooks'
            }
        };

        OnlinePaymentsFE.state = new OnlinePaymentsFE.StateController({
            brand: OnlinePaymentsFE.brand,
            storesUrl: '{$urls.stores.storesUrl}',
            connectionDetailsUrl: '{$urls.connection.getSettingsUrl}',
            currentStoreUrl: '{$urls.stores.currentStoreUrl}',
            stateUrl: '{$urls.integration.stateUrl}',
            versionUrl: '{$urls.version.versionUrl}',
            pageConfiguration: pageConfiguration
        });

        OnlinePaymentsFE.state.display();
        OnlinePaymentsFE.utilities.hideLoader();
    });
</script>
</body>
</html>