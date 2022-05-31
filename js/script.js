let Page = function () {
    let handleForm = function () {
        $("#booking_form").onsubmit(function (e) {
            e.preventDefault();
            console.log('Form was submitted');
        })
    };

    return {
        init: function () {
            handleForm();
        }
    };
}();

$(document).ready(function () {
    Page.init();
});