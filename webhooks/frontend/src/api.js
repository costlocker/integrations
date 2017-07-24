
import { apiUrl, apiAuth } from './state';

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

const headersToObject = (headers) => {
  const list = {};
  for (var header of headers) {
    list[header[0]] = header[1];
  }
  return list;
};

const fetchFromApi = (path: string, isDebug: boolean) =>
  fetch(apiUrl(path), { headers: apiAuth() })
    .then(handleErrors)
    .then(async response => {
      if (isDebug) {
        return {
          headers: headersToObject(response.headers.entries()),
          body: await response.json(),
        };
      }
      return response.json();
    });


const pushToApi = (path: string, data: Object) =>
  fetch(
    apiUrl(path),
    {
      method: 'POST',
      headers: {
        ...apiAuth(),
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify(data),
    }
  )
    .then(handleErrors)
    .then(response => response.json());

export { fetchFromApi, pushToApi };
