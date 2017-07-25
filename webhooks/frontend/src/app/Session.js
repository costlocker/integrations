import { isDevelopmentMode } from '../config';

const session = sessionStorage;

export default class Session {

  getCurrentUser = () => ({
    token: session.getItem('token') || '',
    host: (isDevelopmentMode ? session.getItem('host') : null) || 'https://new-n1.costlocker.com',
  })

  login = ({ host, token }) => {
    sessionStorage.setItem('host', host);
    sessionStorage.setItem('token', token);
  }

  logout = () => session.clear()
}
