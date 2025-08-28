const low = require('lowdb');
const FileSync = require('lowdb/adapters/FileSync');
const { randomUUID } = require('crypto');

const adapter = new FileSync('./data/db.json');
const db = low(adapter);

const simulate401 = false;

const getItems = (dbName, req, res) => {
    const dbItems = db.get(dbName).value();

    return res.status(200).json(dbItems);
};

const getStores = (req, res) => {
    const dbItems = db.get('stores').value();

    return res
        .status(200)
        .json(dbItems.map((s) => ({ storeId: s.storeId, storeName: s.storeName, maintenanceMode: s.maintenanceMode })));
};

const getCurrentStore = (req, res) => {
    const s = db
        .get('stores')
        .find((s) => s.current)
        .value();

    return res.status(200).json({ storeId: s.storeId, storeName: s.storeName, maintenanceMode: s.maintenanceMode });
};

const getOrderStatuses = (req, res) => {
    return getItems('orderStatuses', req, res);
};

const getVersion = (req, res) => {
    return res.status(200).json({
        installed: 'v1.0.1',
        latest: 'v1.2.1'
    });
};

const getStore = (req) => {
    return db.get('stores').find((s) => s.storeId === req.params.storeId);
};

const getStoreState = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    try {
        return res.status(200).json({ state: 'connection' });
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const getConnection = (req, res) => {
    try {
        const connection = getStore(req).value().connection;
        return connection ? res.status(200).json(connection) : res.status(404).json();
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const setStoreConnection = (req, res) => {
    try {
        getStore(req).assign({ connection: req.body }).write();

        return res.status(200).json({});
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const deleteStoreConnection = (req, res) => {
    try {
        getStore(req).assign({ connection: null, state: 'onboarding' }).write();

        return res.status(200).json({});
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const getMerchants = (req, res) => {
    if (simulate401 && Math.random() >= 0.5) {
        //return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    return new Promise((resolve) => {
        setTimeout(() => {
            try {
                resolve(res.status(200).json(getStore(req).value().merchants));
            } catch (e) {
                return res.status(404).json({ message: 'not found' });
            }
        }, 2000);
    });
};

const validateConnection = (req, res) => {
    const result = Math.random() > 0.5;
    if (result) {
        getStore(req).assign({ state: 'dashboard' }).write();

        return res.status(200).json({ status: true });
    }

    return res.status(400).json({ errorCode: 'connection.invalidMerchant' });
};

const getActivePaymentMethods = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    try {
        return res.status(200).json(getStore(req).value().activeMethods || []);
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const getAvailablePaymentMethods = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    return getItems('paymentMethods', req, res);
};

const addPaymentMethod = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    const store = getStore(req);
    const newMethod = { ...req.body };

    if (typeof newMethod.additionalData === 'string') {
        newMethod.additionalData = JSON.parse(newMethod.additionalData);
    }

    if (req.files.length > 0) {
        newMethod.logo = 'https://localhost:3750/' + req.files[0].filename;
    }

    store.assign({ activeMethods: [...(store.value().activeMethods || []), newMethod] }).write();

    return getActivePaymentMethods(req, res);
};

const getPaymentMethod = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    try {
        const method = getStore(req)
            .value()
            .activeMethods.find((m) => m.methodId === req.params.methodId);
        if (method) {
            return res.status(200).json(method);
        }
    } catch (e) {}

    return res.status(404).json({ message: 'not found' });
};

const savePaymentMethod = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    const store = getStore(req);
    const methods = store.value().activeMethods;

    let method = methods.find((m) => m.methodId === req.params.methodId);
    if (method) {
        method = {
            ...method,
            ...req.body
        };

        if (typeof method.additionalData === 'string') {
            method.additionalData = JSON.parse(method.additionalData);
        }

        if (req.files.length > 0) {
            method.logo = 'https://localhost:3750/' + req.files[0].filename;
        }

        store.assign({ activeMethods: methods.map((m) => (m.methodId === method.methodId ? method : m)) }).write();

        return getActivePaymentMethods(req, res);
    }

    return res.status(404).json({ message: 'not found' });
};

const deletePaymentMethod = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    const store = getStore(req);
    const methods = store.value().activeMethods.reduce((result, method) => {
        if (method.methodId === req.params.methodId) {
            return result;
        }

        return [...result, method];
    }, []);
    store.assign({ activeMethods: methods }).write();

    return res.status(200).json({});
};

const getGeneralSettings = (req, res) => {
    return res.status(200).json(getStore(req).value().generalSettings || {});
};

const getOrderMappingSettings = (req, res) => {
    return res.status(200).json(getStore(req).value().orderStatusMapping || {});
};

const getAdyenGivingSettings = (req, res) => {
    return res.status(200).json(getStore(req).value().adyenGiving || {});
};

const getShopEventsNotifications = (req, res) => {
    const page = Number(req.query.page);
    const limit = Number(req.query.limit);
    const start = (page - 1) * limit;
    const end = limit * page;

    // noinspection JSUnresolvedVariable
    const notifications = getStore(req).value().shopNotifications || [];

    return res.status(200).json({
        nextPageAvailable: notifications.length > page * limit,
        notifications: notifications?.slice(start, end) || []
    });
};

const getWebhookEventsNotifications = (req, res) => {
    const page = Number(req.query.page);
    const limit = Number(req.query.limit);
    const start = (page - 1) * limit;
    const end = limit * page;

    // noinspection JSUnresolvedVariable
    const notifications = getStore(req).value().webhookNotifications || [];

    return res.status(200).json({
        nextPageAvailable: notifications.length > page * limit,
        notifications: notifications.slice(start, end)
    });
};

const saveGeneralSettings = (req, res) => {
    try {
        getStore(req).assign({ generalSettings: req.body }).write();

        return res.status(200).json({});
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const saveOrderMappingSettings = (req, res) => {
    try {
        getStore(req).assign({ orderStatusMapping: req.body }).write();

        return res.status(200).json({});
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const saveAdyenGivingSettings = (req, res) => {
    const newData = { ...req.body };
    newData.enableAdyenGiving = newData.enableAdyenGiving === 'true';
    if (newData.enableAdyenGiving) {
        if (req.files && req.files.length > 0) {
            req.files.forEach((file) => {
                const fileUrl = `https://localhost:3750/${file.filename}`;
                if (file.fieldname === 'backgroundImage') {
                    newData.backgroundImage = fileUrl;
                } else if (file.fieldname === 'logo') {
                    newData.logo = fileUrl;
                }
            });
        }
    } else {
        newData.logo = '';
        newData.backgroundImage = '';
    }

    const existingData = getStore(req).value().adyenGiving || {};
    try {
        getStore(req)
            .assign({ adyenGiving: { ...existingData, ...newData } })
            .write();

        return res.status(200).json({});
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const getSystemInfo = (req, res) => {
    return res.status(200).json(db.get('systemInfo').value() || {});
};

const saveSystemInfo = (req, res) => {
    try {
        db.get('systemInfo')
            .assign({ ...req.body })
            .write();

        return res.status(200).json({});
    } catch (e) {
        return res.status(404).json({ message: 'not found' });
    }
};

const webhookValidation = (req, res) => {
    if (simulate401) {
        return res.status(401).json({ errorCode: 'connection.invalidKey' });
    }

    return res.status(200).json({ status: Math.random() > 0.5 });
};

const startIntegrationValidationTask = (req, res) => {
    return res.status(200).json({ queueItemId: randomUUID() });
};

const getIntegrationValidationStatus = (req, res) => {
    return res.status(200).json({ status: Math.random() > 0.5, finished: Math.random() > 0.5 });
};

module.exports = {
    getStores,
    getCurrentStore,
    getOrderStatuses,
    getVersion,
    getStoreState,
    getShopEventsNotifications,
    getWebhookEventsNotifications,

    getGeneralSettings,
    saveGeneralSettings,

    getOrderMappingSettings,
    saveOrderMappingSettings,

    getAdyenGivingSettings,
    saveAdyenGivingSettings,

    getSystemInfo,
    saveSystemInfo,

    webhookValidation,
    startIntegrationValidationTask,
    getIntegrationValidationStatus,

    getConnection,
    setStoreConnection,
    deleteStoreConnection,
    getMerchants,
    validateConnection,

    getActivePaymentMethods,
    getAvailablePaymentMethods,

    addPaymentMethod,
    getPaymentMethod,
    savePaymentMethod,
    deletePaymentMethod
};
