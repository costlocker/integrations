
export default class Steps {
  constructor(router, titles) {
    this.router = router;
    this.currentStep = 1;
    this.titles = titles;
  }

  getCurrentStep() {
    return this.currentStep;
  }

  isInvalidStep(step) {
    return step > this.currentStep;
  }

  getPreviousTitle() {
    return this.titles[this.currentStep - 2];
  }

  getNextTitle() {
    return this.titles[this.currentStep];
  }

  goToNextStep = (e) => {
    this.goToStep(this.currentStep + 1, e);
  }

  goToPreviousStep = (e) => {
    this.goToStep(this.currentStep - 1, e);
  }

  goToStep = (step, e) => {
    if (e) {
      e.preventDefault();
    }
    this.currentStep = step;
    this.router.stateService.go(`wizard.${step}`, undefined, { location: true });
  }
}
