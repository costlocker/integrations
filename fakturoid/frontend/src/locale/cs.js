import React from 'react';

export default {
  // app (Router.js, Loading.js)
  page: {
    invoice: 'Nová faktura',
    invoices: 'Faktury',
    invoicesHistory: 'Dříve naimportované faktury',
    login: 'Přihlášení'
  },
  loading: {
    app: 'Načítám Costlocker & Fakturoid addon',
    invoice: 'Načítám kontakty z Fakturoidu a billing z Costlockeru',
    createInvoice: 'Vytvářím fakturu',
  },
  notify: {
    reloadSubjects: 'Kontakty načteny',
    unknownSubject: 'Vyberte existující kontakt z Fakturoida',
    requiredFakturoid: 'Před vytvořením faktury se musíte přihlásit do Fakturoidu',
  },
  // Login.js
  login: {
    accounts: 'Uživatelské účty',
    error: 'Chyba během přihlášení',
    email: 'E-mail',
    slug: 'Fakturoid slug (subdoména)',
    token: 'API token',
    tokenHelp: 'Přejít na stránku s tokenem',
    requiredCostlocker: 'Nejdříve se musíte přihlásit do Costlockeru',
    apiAggreement: 'Souhlasím s přístupem k Fakturoid API',
    loginCostlocker: 'Přihlásit se do Costlockeru',
    loginFakturoid: 'Přihlásit se do Fakturoidu',
    switchAccount: 'Změnit účet',
  },
  // InvoiceList.js
  invoiceTypes: {
    '': 'Všechny',
    'invoice': 'Faktury',
    'proforma': {
      'full': 'Zálohová faktura (plná)',
      'partial': 'Zálohová faktura (částečná)'
    },
  },
  invoiceModal: {
    checkAll: ({ count }) => `Vybrat vše (${count})`,
    submit: 'Přidat vybrané',
  },
  invoiceLines: {
    people: 'Lidé',
    peopleAndActivites: 'Lidé a aktivity',
    activities: "Aktivity",
    expenses: "Náklady",
    discount: 'Sleva',
    other: 'Ostatní',
    actions: {
      peopleAndActivites: 'Přidat lidi nebo aktivity',
      expenses: 'Přidat náklady',
      discount: 'Přidat slavu',
      reset: 'Reset řádků',
      newLine: 'Přidat nový řádek',
    },
    item: {
      quantity: 'Množství',
      unit: 'Jednotka',
      name: 'Popis',
      vat: 'DPH',
      unit_amount: 'Cena za jednotku',
      total_amount: 'Celková cena',
      invalid: 'Řádek bude ignorován a nebude přenesen do Fakturoidu',
    },
    units: {
      quantity: 'ks',
      time: 'h',
    },
  },
  editor: {
    type: 'Typ faktury',
    subject: 'Kontakt ve Fakturoid',
    createSubject: 'Přidat nový kontakt',
    outdatedSubjects: 'Nevidíte nějaký kontakt?',
    reloadSubjects: 'Zkuste obnovit seznam kontaktů',
    issuedAt: 'Datum vystavení',
    due: 'Splatnost (počet dnů)',
    dueDay: 'vychází na',
    orderNumber: 'Číslo objednávky',
    noteBeforeLines: 'Poznámka před položkami faktury',
    note: {
      title: 'Poznámka',
      placeholder: 'Skrytá poznámka u faktury',
    },
    advancedSettings: {
      up: 'méně možnosti',
      down: 'další možnosti',
    },
    revenueError: {
      title: ({ billedAmount }) => `Celková částka musí být menší nebo rovna '${billedAmount}', jinak by projektové revenue bylo menší než billing`,
      error: ({ linesAmount }) => `Nemůžete vyfakturovat '${linesAmount}', snižte počet kusů nebo cenu v jednotlivých řádcích`,
    },
    submit: 'Vytvořit fakturu',
  },
  summary: {
    total: 'Celkem',
    totalWithVAT: 'Celkem bez DPH',
    totalWithoutVAT: 'Celkem s DPH',
    vat: 'DPH',
  },
  invalidInvoice: {
    notDraft: {
      title: 'Neplatný stav billing',
      error: 'Billing už je označený jako vystavený v Costlockeru',
    },
    unknownBilling: {
      title: 'Neznámý billing',
      error: 'Billing neexistuje v Costlockeru, nebo nemáte přístup k danému projektu',
    },
  },
  invoices: {
    client: 'Klient',
    id: 'ID',
    issued: 'Vystaveno',
    price: 'Cena',
    priceVAT: 'Cena s DPH',
    links: 'Odkazy',
    linkProject: 'Otevřít projekt',
    linkInvoice: 'Otevřít fakturu',
    noResults: 'Žádná faktura nenalezena',
  },
  search: {
    query: 'Najít',
  },
  // Invoice.js
  tutorial: {
    title: 'Vše je připraveno!',
    description: ({ costlockerButton }) => <span>V Costlockeru teď uvidíte {costlockerButton} v Billingu každého projektu</span>,
    linkCostlocker: 'Super, vemte mě zpátky do Costlockeru!',
  },
  createdInvoice: {
    title: 'Faktura byla úspěšně vytvořena!',
    description: () => <span>Faktura byla na importovaná do Fakturoid.<br /> Teď můžete přejít do Fakturoidu nebo se vrátit zpět do Costlockeru.</span>,
    linkFakturoid: 'Otevřít ve Fakturoidu',
    linkSeparator: 'nebo',
    linkCostlocker: 'Zpět do Costlockeru',
  },
  // DisabledAddon.js
  disabledAddon: {
    title: 'Fakturoid addon je vypnutý',
    ask: 'Požádejte Vašeho ownera, aby',
    enable: 'povolil addon v nastavení',
    logout: ({ company }) => `Odhlásit a změnit společnost ${company}`,
  },
  // ErrorPage.js
  error: {
    title: 'Jejda, něco se nepovedlo',
    description: ({ currentUrl }) => <span>
      Zkuste <a href={currentUrl}>znovu načíst stránku</a>. Pokud se bude chyba opakovat, tak nám prosím zašlete následující text na
    </span>,
  },
};
