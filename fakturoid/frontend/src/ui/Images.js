import React from 'react';

const importAllLogos = () => {
  const r = require.context('../images/logo', false, /\.(png)$/);
  let images = {};
  r.keys().forEach((item, index) => {
    images[item.replace('./', '')] = r(item);
  });
  return images;
};
const logos = importAllLogos();

const Image = (props) => <img {...props} alt="" />

const ImageTooltip = ({ url }) =>
  <div className="image-tooltip">
    <i className="fa fa-question-circle" />
    <Image src={url} />
  </div>;

const Logo = ({ app }) =>
  <img src={logos[`${app}.png`]} srcset={`${logos[`${app}@2x.png`]} 2x`}
    title={app} alt={app} className="logo" />;

export { Image, ImageTooltip, Logo };
