import moment from 'moment';

let messages = {};

const addLocaleData = (locale) => {
  messages = require(`./locale/${locale}.js`).default;
  require(`moment/locale/${locale}.js`);
  moment.locale(locale);
};

const trans = (message, values) => {
  const keys = message.split('.');
  let result = messages;
  keys.forEach(key => {
    result = result[key];
  });
  return typeof result === 'function' ? result(values) : result;
}

export { addLocaleData, trans };
