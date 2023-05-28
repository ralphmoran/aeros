/******/
(() => { // webpackBootstrap
  /******/
  "use strict";
  var __webpack_exports__ = {};
  /*!*******************************************************!*\
    !*** ../demo2/src/js/custom/modals/create-account.js ***!
    \*******************************************************/


  // Class definition
  var KTCreateAccount = function () {
    // Elements
    var modal;
    var modalEl;

    var stepper;
    var form;
    var formSubmitButton;

    // Variables
    var stepperObj;
    var validations = [];

    // Private Functions
    var initStepper = function () {
      // Initialize Stepper
      stepperObj = new KTStepper(stepper);

      // Validation before going to next page
      stepperObj.on('kt.stepper.next', function (stepper) {
        console.log('stepper.next');

        // Validate form before change stepper step
        var validator = validations[stepper.getCurrentStepIndex() - 1]; // get validator for currnt step

        if (validator) {
          validator.validate().then(function (status) {
            console.log('validated!');

            if (status == 'Valid') {
              stepper.goNext();

              KTUtil.scrollTop();
            } else {
              Swal.fire({
                text: "Sorry, looks like there are some errors detected, please try again.",
                icon: "error",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                  confirmButton: "btn btn-light"
                }
              }).then(function () {
                KTUtil.scrollTop();
              });
            }
          });
        } else {
          stepper.goNext();

          KTUtil.scrollTop();
        }
      });

      // Prev event
      stepperObj.on('kt.stepper.previous', function (stepper) {
        console.log('stepper.previous');

        stepper.goPrevious();
        KTUtil.scrollTop();
      });
    }

    var handleForm = function () {
      formSubmitButton.addEventListener('click', function (e) {
        // Prevent default button action
        e.preventDefault();

        // Disable button to avoid multiple click 
        formSubmitButton.disabled = true;

        // Show loading indication
        formSubmitButton.setAttribute('data-kt-indicator', 'on');

        // Simulate form submission
        setTimeout(function () {
          // Hide loading indication
          formSubmitButton.removeAttribute('data-kt-indicator');

          // Enable button
          formSubmitButton.disabled = true;

          // Show popup confirmation. For more info check the plugin's official documentation: https://sweetalert2.github.io/
          Swal.fire({
            text: "Form has been successfully submitted!",
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Ok, got it!",
            customClass: {
              confirmButton: "btn btn-primary"
            }
          }).then(function (result) {
            if (result.isConfirmed) {
              if (modal) {
                modal.hide(); // close modal
              }
              //form.submit(); // Submit form
            }
          });
        }, 2000);
      });
    }

    var initValidation = function () {
      // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
      // Step 1
      validations.push(FormValidation.formValidation(
        form, {
        fields: {
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          excluded: new FormValidation.plugins.Excluded(),
          bootstrap: new FormValidation.plugins.Bootstrap5({
            rowSelector: '.fv-row',
            eleInvalidClass: '',
            eleValidClass: ''
          })
        }
      }
      ));

      // Step 2
      validations.push(FormValidation.formValidation(
        form, {
        fields: {

        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          excluded: new FormValidation.plugins.Excluded(),
          // Bootstrap Framework Integration
          bootstrap: new FormValidation.plugins.Bootstrap5({
            rowSelector: '.fv-row',
            eleInvalidClass: '',
            eleValidClass: ''
          })
        }
      }
      ));


    }

    return {
      // Public Functions
      init: function () {
        // Elements
        modalEl = document.querySelector('#kt_modal_create_account');

        if (modalEl) {
          modal = new bootstrap.Modal(modalEl);
        }

        stepper = document.querySelector('#capPartner_stepper');


        form = stepper.querySelector('#cap_pat_form');

        initStepper();
        initValidation();
      }
    };
  }();

  // On document ready
  KTUtil.onDOMContentLoaded(function () {
    KTCreateAccount.init();
  });
  /******/
})();
  //# sourceMappingURL=deal_title.js.map