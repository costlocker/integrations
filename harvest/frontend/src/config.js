
const isDevelopmentMode = process.env.NODE_ENV === 'development';

const productionHost = '';
const apiHost = isDevelopmentMode && process.env.REACT_APP_API_HOST ? process.env.REACT_APP_API_HOST : productionHost;
const apiUrl = `${apiHost}/api`;

export { isDevelopmentMode, apiUrl }
