
import en from './locale/en';
const messages = en;

const trans = (message, values) => {
  const keys = message.split('.');
  let result = messages;
  keys.forEach(key => {
    result = result[key];
  });
  return typeof result === 'function' ? result(values) : result;
}

export { trans };
