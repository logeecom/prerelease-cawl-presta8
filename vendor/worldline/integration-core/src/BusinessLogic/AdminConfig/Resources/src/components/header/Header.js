if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

if (!window.OnlinePaymentsFE.components) {
    window.OnlinePaymentsFE.components = {};
}

(function () {
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    /**
     * @typedef HeaderModel
     *
     * @property {string?} brand
     * @property {LinkDropDownComponentModel?} linkDropDown
     * @property {string} page
     * @property {string} mode
     * */

    /**
     * Header component.
     *
     * @param {HeaderModel} params
     * @returns {HTMLElement}
     * @constructor
     */
    const Header = ({
        brand,
        linkDropDown,
        page,
        mode
    }) => {
        const { elementGenerator: generator, translationService } = OnlinePaymentsFE;
        const header = generator.createElement('div');
        const navigation = generator.createElement('div', 'op-main-header-navigation');
        const leftPart = generator.createElement('div', 'op-main-header-left');
        const logoWrapper = generator.createElement('div', 'op-main-logo');
        logoWrapper.appendChild(generator.createElement(
            'img', 'op-small-logo', '',
            { 'alt': 'logo', 'src': './assets/images/' + brand + '-small.svg' }
        ));
        logoWrapper.appendChild(generator.createElement(
            'img', 'op-big-logo',
            '', { 'alt': 'logo', 'src': './assets/images/' + brand + '.svg' }
        ));

        const badge = generator.createElement(
            'span',
            'op-header-badge',
            translationService.translate('general.direct').toUpperCase()
        );
        const documentationWrapper = generator.createElement('div', 'op-link-dropdown');
        const documentation = generator.createLinkDropdownField(linkDropDown);
        documentationWrapper.appendChild(documentation);
        const rightPart = generator.createElement('div', 'op-main-header-right');
        const menuItem = generator.createElement('button', 'op-header-menu-item');
        menuItem.addEventListener('click', function () {
            if (menuItem.classList.contains('op-clicked')) {
                let menu = document.querySelector('.op-header-menu');

                if (menu) {
                    menu.remove();
                }
            } else {
                let mainPage = OnlinePaymentsFE.templateService.getMainPage();
                let menuBox = OnlinePaymentsFE.components.HeaderMenu.create(linkDropDown);
                menuBox.classList.add('op-open');

                mainPage.firstChild.appendChild(menuBox);
            }

            menuItem.classList.toggle('op-clicked');
        })
        const paymentsTab = generator.createElement(
            'a',
            'op-header-tab op-header-payment',
            translationService.translate('general.payments')
        );
        paymentsTab.href = '#payments';
        const monitoringTab = generator.createElement(
            'a',
            'op-header-tab op-header-monitoring',
            translationService.translate('general.monitoring')
        );
        monitoringTab.href = '#monitoring';
        const settingsTab = generator.createElement(
            'a',
            'op-header-tab op-header-settings',
            translationService.translate('general.settings')
        );
        settingsTab.href = '#settings';

        rightPart.appendChild(menuItem);
        rightPart.appendChild(paymentsTab);
        rightPart.appendChild(monitoringTab);
        rightPart.appendChild(settingsTab);

        leftPart.appendChild(logoWrapper);
        leftPart.appendChild(badge);
        leftPart.appendChild(documentationWrapper);

        navigation.appendChild(leftPart);
        navigation.appendChild(rightPart);

        const titleHeader = generator.createElement('div', 'op-main-title-header');
        const title = generator.createElement('div', 'op-main-title', '');
        const state = generator.createElement('div', 'op-status');
        state.id = 'op-status';

        if (mode !== undefined) {
            const modeDiv = generator.createElement('div', 'op-mode');
            const indicator = generator.createElement('span', 'op-icon');

            if (mode === 'test') {
                indicator.classList.add('op-sandbox');
            } else {
                indicator.classList.add('op-live');
            }

            const modeText = generator.createElement('p', 'op-mode-text', 'general.mode.' + mode);
            modeDiv.appendChild(indicator);
            modeDiv.appendChild(modeText);
            state.appendChild(modeDiv);
        }

        const stores = generator.createElement('div', '');
        stores.id = 'op-store-selector';
        const storesWrapper = generator.createElement('div', 'op-single-select-dropdown');
        const storeButton = generator.createElement('button', 'opp-dropdown-button');
        const selectedStore = generator.createElement('span', 'ops--selected');
        const list = generator.createElement('ul', 'opp-dropdown-list');
        storeButton.appendChild(selectedStore);
        storesWrapper.appendChild(storeButton);
        storesWrapper.appendChild(list);
        stores.appendChild(storesWrapper);
        state.appendChild(stores);

        titleHeader.appendChild(title);
        titleHeader.appendChild(state);

        const responsiveHeader = generator.createElement('div', 'op-main-responsive-header');
        responsiveHeader.appendChild(state.cloneNode(true));

        header.appendChild(navigation);
        header.appendChild(titleHeader);
        header.appendChild(responsiveHeader);

        return header;
    };

    OnlinePaymentsFE.components.Header = {
        /**
         * @param {HeaderModel} config
         * @returns {HTMLElement}
         */
        create: (config) => Header(config)
    };
})();
