const translation = require('./lang/en.json');

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
require('./services/AjaxService');
require('./services/ResponseService');
require('./services/TranslationService');
require('./services/UtilityService');
require('./services/ValidationService');

require('./components/data-table/DataTableComponent');
require('./components/dropdown/DropdownComponent');
require('./components/multiselect-dropdown/MultiselectDropdownComponent');
require('./components/modal/ModalComponent');
require('./components/table-filter/TableFilterComponent');
require('./components/header/Header.js');
require('./components/footer/Footer');
require('./components/link-dropdown/LinkDropDownComponent');
require('./components/sliding-modal/SlidingModal');

require('./controllers/StateController');
require('./controllers/ConnectionController');
require('./controllers/MonitoringController');
require('./controllers/PaymentsController');
require('./controllers/SettingsController');

