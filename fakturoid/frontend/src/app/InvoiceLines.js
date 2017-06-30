
import { Map } from 'immutable';

export default class InvoiceLines {
  constructor(cursor) {
    this.cursor = cursor;
  }

  calculateTotaAmount() {
    return this.deref().reduce((sum, item) => item.get('total_amount') + sum, 0);
  }

  addDefaultIfIsEmpty({ name, amount }) {
    if (!this.deref().size) {
      this.update(lines => lines.add(Map({
        name: name,
        quantity: 1,
        unit: 'ks',
        unit_amount: amount,
        total_amount: amount,
      })));
    }
  }

  addExpenses = (expenses) => () => {
    this.update(lines => {
      let updated = lines;
      expenses.forEach(expense => {
        updated = updated.add(Map({
          name: expense.expense.description,
          quantity: 1,
          unit: 'ks',
          unit_amount: expense.expense.billed.total_amount,
          total_amount: expense.expense.billed.total_amount,
        }));
      });
      return updated;
    });
  }

  addActivities = (peoplecosts) => () => {
    this.update(lines => {
      let updated = lines;
      peoplecosts.forEach(activityCost => {
        updated = updated.add(Map({
          name: activityCost.activity.name,
          quantity: activityCost.hours.budget,
          unit: 'h',
          unit_amount: activityCost.activity.hourly_rate,
          total_amount: activityCost.activity.hourly_rate * activityCost.hours.budget,
        }));
      });
      return updated;
    });
  }

  addPeople = (peoplecosts) => () => {
    this.update(lines => {
      let updated = lines;
      peoplecosts.forEach(activityCost => {
        activityCost.people.forEach(personCost => {
          updated = updated.add(Map({
            name: `${activityCost.activity.name} - ${personCost.person.first_name} ${personCost.person.last_name}`,
            quantity: personCost.hours.budget,
            unit: 'h',
            unit_amount: activityCost.activity.hourly_rate,
            total_amount: activityCost.activity.hourly_rate * personCost.hours.budget,
          }));
        });
      });
      return updated;
    });
  }

  addEmptyLine = () => () => {
    this.update(lines => lines.add(Map({
      name: '',
      quantity: 0,
      unit: 'ks',
      unit_amount: 0,
      total_amount: 0,
    })));
  }

  removeAllLines = () => () => {
    this.update(lines => lines.clear());
  }

  updateFieldInLine = (field, line) => (e) => {
    let updated = line.set(field, e.target.value);
    updated = updated.set('total_amount', updated.get('quantity') * updated.get('unit_amount'))
    this.update(lines => lines.delete(line).add(updated));
  }

  removeLine = (line) => () => this.update(lines => lines.delete(line))

  hasMultipleLines = () => this.deref().size > 1

  map = (callback) => this.deref().map(callback)

  update = (callback) =>  this.cursor.update(callback)

  deref = (callback) => this.cursor.deref()
}
