import React from 'react'
import ReactDOMServer from 'react-dom/server'

import Loading from './Loading'

export function prerender() {
  return ReactDOMServer.renderToString(<Loading title="Načítám Costlocker & Fakturoid addon" isTranslated />);
}
