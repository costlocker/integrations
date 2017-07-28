
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

  addItems = (items) => {
    this.update(lines => {
      let updated = lines;
      updated = this.removeDefaultIfExists(updated);
      items.forEach(item => {
        updated = this.addLine(updated, item);
      });
      return updated;
    });
  }

  getAllActivities = (items) => () => items.map(
    activityCost => ({
      id: `activity-${activityCost.item.activity_id}`,
      name: activityCost.activity.name,
      quantity: activityCost.hours.budget,
      unit: 'h',
      unit_amount: activityCost.activity.hourly_rate,
      total_amount: activityCost.activity.hourly_rate * activityCost.hours.budget,
    })
  )

  getAllPeople = (items) => () => {
    const people = {};
    items.map(activityCost => (
      activityCost.people.map(personCost => {
        const id = `people-${activityCost.item.activity_id}-${personCost.item.person_id}`;
        people[id] = {
          id: id,
          name: `${activityCost.activity.name} - ${personCost.person.first_name} ${personCost.person.last_name}`,
          quantity: personCost.hours.budget,
          unit: 'h',
          unit_amount: activityCost.activity.hourly_rate,
          total_amount: activityCost.activity.hourly_rate * personCost.hours.budget,
        };
      })
    ))
    return Object.values(people);
  }

  getAllExpenses = (items) => () => items.map(
    expense => ({
      id: `expense-${expense.item.expense_id}`,
      name: expense.expense.description,
      quantity: 1,
      unit: 'ks',
      unit_amount: expense.expense.billed.total_amount,
      total_amount: expense.expense.billed.total_amount,
    })
  )

  addDiscount = (discount) => () => {
    if (discount <= 0) {
      return;
    }
    this.update(lines => this.addLine(lines, {
      id: `discount`,
      name: 'Discount',
      quantity: 1,
      unit: 'ks',
      unit_amount: -discount,
      total_amount: -discount,
    }));
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

  removeDefaultIfExists = (lines) => {
    return lines.delete('default');
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

  getGroupedLines = () => {
    const groups = {};
    this.deref().forEach(item => {
      const type = item.get('id').split('-')[0];
      if (!groups[type]) {
        groups[type] = [];
      }
      groups[type].push(item);
    });
    const getGroup = (type) => groups[type] || [];
    return [
      { title: 'People and Activities', items: getGroup('people').concat(getGroup('activity')) },
      { title: 'Expenses', items: getGroup('expense') },
      { title: 'Discount', items: getGroup('discount') },
      { title: 'Other', items: getGroup('default').concat(getGroup('empty')) },
    ];
  }

  map = (callback) => this.deref().valueSeq().map(callback)

  addLine = (lines, rawData) => lines.set(rawData.id, Map(rawData))

  update = (callback) => this.cursor.update(callback)

  deref = (callback) => this.cursor.deref()
}
