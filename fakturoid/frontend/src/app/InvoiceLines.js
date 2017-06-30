
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
      this.update(lines => this.addLine(lines, {
        id: 'default',
        name: name,
        quantity: 1,
        unit: 'ks',
        unit_amount: amount,
        total_amount: amount,
      }));
    }
  }

  addExpenses = (expenses) => () => {
    this.update(lines => {
      let updated = lines;
      expenses.forEach(expense => {
        updated = this.addLine(updated, {
          id: `expense-${expense.item.expense_id}`,
          name: expense.expense.description,
          quantity: 1,
          unit: 'ks',
          unit_amount: expense.expense.billed.total_amount,
          total_amount: expense.expense.billed.total_amount,
        });
      });
      return updated;
    });
  }

  addActivities = (peoplecosts) => () => {
    this.update(lines => {
      let updated = lines;
      peoplecosts.forEach(activityCost => {
        updated = this.addLine(updated, {
          id: `activity-${activityCost.item.activity_id}`,
          name: activityCost.activity.name,
          quantity: activityCost.hours.budget,
          unit: 'h',
          unit_amount: activityCost.activity.hourly_rate,
          total_amount: activityCost.activity.hourly_rate * activityCost.hours.budget,
        });
      });
      return updated;
    });
  }

  addPeople = (peoplecosts) => () => {
    this.update(lines => {
      let updated = lines;
      peoplecosts.forEach(activityCost => {
        activityCost.people.forEach(personCost => {
          updated = this.addLine(updated, {
            id: `activity-${activityCost.item.activity_id}-${personCost.item.person_id}`,
            name: `${activityCost.activity.name} - ${personCost.person.first_name} ${personCost.person.last_name}`,
            quantity: personCost.hours.budget,
            unit: 'h',
            unit_amount: activityCost.activity.hourly_rate,
            total_amount: activityCost.activity.hourly_rate * personCost.hours.budget,
          });
        });
      });
      return updated;
    });
  }

  addEmptyLine = () => () => {
    this.update(lines => this.addLine(lines, {
      id: `empty-${Math.random().toString(36).substring(7)}`,
      name: '',
      quantity: 0,
      unit: 'ks',
      unit_amount: 0,
      total_amount: 0,
    }));
  }

  removeAllLines = () => () => {
    this.update(lines => lines.clear());
  }

  updateFieldInLine = (field, line) => (e) => {
    this.update(lines => (
      lines.updateIn([line.get('id')], value => {
        let updated = value.set(field, e.target.value);
        return updated.set('total_amount', updated.get('quantity') * updated.get('unit_amount'));
      })
    ));
  }

  removeLine = (line) => () => this.update(lines => lines.delete(line.get('id')))

  hasMultipleLines = () => this.deref().size > 1

  map = (callback) => this.deref().valueSeq().map(callback)

  addLine = (lines, rawData) => lines.set(rawData.id, Map(rawData))

  update = (callback) =>  this.cursor.update(callback)

  deref = (callback) => this.cursor.deref()
}
