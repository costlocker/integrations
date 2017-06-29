import React from 'react';
import { appHost } from '../config';

export default function InvoiceTutorial() {
  const billingOutlookUrl = `${appHost}/dashboard/billing-outlook`
  return <div>
    <ol>
      <li>Go to <a href={billingOutlookUrl}>Billing Outlook in Costlocker</a></li>
      <li>Go to selected project invoice</li>
      <li>Click on <strong>Create invoice</strong></li>
    </ol>
  </div>;
}
