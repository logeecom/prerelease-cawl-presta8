if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

if (!window.OnlinePaymentsFE.components) {
    window.OnlinePaymentsFE.components = {};
}

(function () {
    /**
     * @param {LinkDropDownComponentModel} linkDropDown
     *
     * @returns {*|HTMLElement}
     * @constructor
     */
    const HeaderMenu = (linkDropDown) => {
        const {
            elementGenerator: generator,
            translationService
        } = OnlinePaymentsFE;

        let content = generator.createElement('div', 'op-header-menu-content');
        const paymentsTab = generator.createElement(
            'a',
            'op-header-tab op-header-payment',
            translationService.translate('general.payments')
        );
        paymentsTab.href = '#payments';
        paymentsTab.addEventListener('click', function () {
            let menu = document.querySelector('.op-header-menu');
            let menuItem = document.querySelector('.op-clicked');

            if (menu) {
                menu.remove();
            }

            menuItem.classList.toggle('op-clicked');
        });

        const monitoringTab = generator.createElement(
            'a',
            'op-header-tab op-header-monitoring',
            translationService.translate('general.monitoring')
        );
        monitoringTab.href = '#monitoring';
        monitoringTab.addEventListener('click', function () {
            let menu = document.querySelector('.op-header-menu');
            let menuItem = document.querySelector('.op-clicked');

            if (menu) {
                menu.remove();
            }

            menuItem.classList.toggle('op-clicked');
        })
        const settingsTab = generator.createElement(
            'a',
            'op-header-tab op-header-settings',
            translationService.translate('general.settings')
        );
        settingsTab.href = '#settings';
        settingsTab.addEventListener('click', function () {
            let menu = document.querySelector('.op-header-menu');
            let menuItem = document.querySelector('.op-clicked');

            if (menu) {
                menu.remove();
            }

            menuItem.classList.toggle('op-clicked');
        });
        const helpTab = generator.createElement(
            'a',
            'op-header-tab op-header-help',
            translationService.translate('general.needHelp')
        );
        helpTab.href = translationService.translate(OnlinePaymentsFE.brand.code + '.links.help');
        const documentation = generator.createElement(
            'div',
            'op-header-tab op-documentation-wrapper'
        );
        const documentationTitle = generator.createElement(
            'span',
            'op-header-documentation',
            translationService.translate('general.documentation')
        );

        documentation.appendChild(documentationTitle);
        const docsLinks = generator.createElement('ul', 'op-documents-list');

        linkDropDown.options.forEach((option) => {
            const listItem = generator.createElement(
                'li',
                'op-documents-link-wrapper'
            );
            const listTitle = generator.createElement(
                'span',
                'op-documents-link',
                translationService.translate(option.label)
            );

            listTitle.addEventListener('click', () => {
                window.open(option.link, '_blank');
            });
            listItem.appendChild(listTitle);

            docsLinks.appendChild(listItem);
        });
        documentation.appendChild(docsLinks);

        content.appendChild(paymentsTab);
        content.appendChild(monitoringTab);
        content.appendChild(settingsTab);
        content.appendChild(helpTab);
        content.appendChild(documentation);

        const modal = generator.createElement('div', 'op-header-menu');

        modal.appendChild(content);

        return modal;
    };

    OnlinePaymentsFE.components.HeaderMenu = {
        /**
         * @param {LinkDropDownComponentModel} linkDropDown
         * @returns {HTMLElement}
         */
        create: (linkDropDown) => HeaderMenu(linkDropDown)
    };
})();