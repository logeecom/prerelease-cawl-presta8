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
     * @typedef FooterModel
     *
     * @property {string} newVersion
     * @property {string} installedVersion
     * */

    /**
     * Footer component.
     *
     * @param {FooterModel} params
     * @returns {HTMLElement}
     * @constructor
     */
    const Footer = ({
        newVersion,
        installedVersion
    }) => {
        const { elementGenerator: generator, translationService } = OnlinePaymentsFE;
        const footer = generator.createElement('div', 'op-footer');

        if (newVersion !== installedVersion) {
            const update = generator.createElement('div', 'op-update', translationService.translate('general.version.update', [newVersion]));
            footer.appendChild(update);
        }

        const version = generator.createElement('p', 'op-version', installedVersion);
        footer.appendChild(version);

        return footer;
    };

    OnlinePaymentsFE.components.Footer = {
        /**
         * @param {FooterModel} config
         * @returns {HTMLElement}
         */
        create: (config) => Footer(config)
    };
})();