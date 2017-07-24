
import { apiUrl, apiAuth } from './state';
import { proxyUrl } from './config';

const handleErrors = (response) => {
  if (!response.ok) {
    const error = new Error('Invalid API response');
    error.status = response.status;
    error.stack = `${response.url}\n${response.status} ${response.statusText}`;
    error.response = response;
    throw error;
  }
  return response;
};

const fetchViaProxy = (url, settings, isDebug: boolean) =>
  fetch(proxyUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        method: settings.method || 'GET',
        url: url,
        isDebug: isDebug,
        headers: settings.headers,
        body: settings.body || null,
      }),
  });

const fetchFromApi = (path: string, isDebug: boolean) =>
  fetchViaProxy(apiUrl(path), { headers: apiAuth() }, isDebug)
    .then(handleErrors)
    .then(response => {
      return response.json();
    });


const pushToApi = (path: string, dataOrMethod: Object) =>
  fetchViaProxy(
    apiUrl(path),
    {
      method: dataOrMethod === 'DELETE' ? dataOrMethod : 'POST',
      headers: {
        ...apiAuth(),
        'Content-Type': 'application/json',
      },
      body: dataOrMethod === 'DELETE' ? null : JSON.stringify(dataOrMethod),
    }
  )
    .then(handleErrors)
    .then(response => response.json());

export { fetchFromApi, pushToApi };
