import moment from 'moment';

let messages = {};

const addLocaleData = (locale) => {
  messages = require(`./locale/${locale}.js`).default;
  moment.locale(locale, require(`moment/locale/${locale}.js`));
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
