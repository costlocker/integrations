
const isDevelopmentMode = process.env.NODE_ENV === 'development';

const productionApp = 'https://new.costlocker.com';
const productionHost = '';

const appHost = isDevelopmentMode && process.env.REACT_APP_CL_HOST ? process.env.REACT_APP_CL_HOST : productionApp;
const apiHost = isDevelopmentMode && process.env.REACT_APP_API_HOST ? process.env.REACT_APP_API_HOST : productionHost;
const apiUrl = `${apiHost}/api`;

const serverTimezoneOffsetInHours = isDevelopmentMode ? 2 : 0;

export { isDevelopmentMode, apiUrl, appHost, serverTimezoneOffsetInHours }
