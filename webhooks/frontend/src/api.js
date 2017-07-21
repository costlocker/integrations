
import { apiUrl } from './state';

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

const fetchFromApi = (path: string) =>
  fetch(`${apiUrl()}${path}`, { credentials: 'include' })
    .then(handleErrors)
    .then(response => response.json());

const pushToApi = (path: string, data: Object) =>
  fetch(
    `${apiUrl()}${path}`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify(data),
    }
  )
    .then(handleErrors)
    .then(response => response.json());

export { fetchFromApi, pushToApi };
