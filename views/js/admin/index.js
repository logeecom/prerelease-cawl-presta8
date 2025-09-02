const translation = require('../../lang/en.json');

window.OnlinePaymentsFE = {
    ...(window.OnlinePaymentsFE || {}),
    ...{
        ...window.OnlinePaymentsFE?.config || {},
        components: {},
        models: {},
        translations: {
            default: translation,
            current: translation
        }
    }
};
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/AjaxService');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/ResponseService');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/TranslationService');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/TemplateService');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/UtilityService');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/ValidationService');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/ElementGenerator');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/PageControllerFactory');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/services/Sanitizer');

require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/data-table/DataTableComponent');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/dropdown/DropdownComponent');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/header-menu/HeaderMenu');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/multiselect-dropdown/MultiselectDropdownComponent');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/modal/ModalComponent');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/table-filter/TableFilterComponent');
require('./Override/Header');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/footer/Footer');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/link-dropdown/LinkDropDownComponent');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/sliding-modal/SlidingModal');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/components/translatable-label/TranslatableLabel');

require('./Override/StateController');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/controllers/ConnectionController');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/controllers/MonitoringController');
require('../../../vendor/worldline/integration-core/src/BusinessLogic/AdminConfig/Resources/src/controllers/PaymentsController');
require('./Override/SettingsController');
