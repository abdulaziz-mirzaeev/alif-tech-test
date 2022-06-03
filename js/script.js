function getToastTemplate(content, className = 'bg-success') {
    return `<div class="toast align-items-center text-white ${className} border-0" role="alert" aria-live="assertive" aria-atomic="true">` +
        `<div class="d-flex">` +
        `<div class="toast-body">` +
        content +
        `</div>` +
        `<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>` +
        `</div>` +
        `</div>`;
}

function notify(message, className = 'bg-success') {
    let toast = getToastTemplate(message, className);
    $('#message-container').append(toast);
    new bootstrap.Toast($('#message-container .toast:last-child')).show();
}

let Page = function () {
    let basicSetup = function () {
        $(document).ajaxError(function (event, request, settings, error) {
            if (request.responseJSON.errors) {
                messages = request.responseJSON.errors;
                messages.forEach(function (item) {
                    notify('Error: '+item, 'bg-danger');
                })
            } else {
                notify('Error: ' + request.responseText, 'bg-danger')
            }
        })
    };
    let handleRooms = function () {
        $.ajax({
            url: '/backend/index.php',
            data: {r: 'getRooms'},
            success: function (response) {
                response.forEach(function (item) {
                    let option = $(`<option value="${item.id}">${item.name}</option>`);
                    $("#room_id").append(option);
                })
            }
        })
    };

    let handleForm = function () {
        $("#booking_form").on('submit', function (e) {
            e.preventDefault();

            let data = {
                room_id: $("#room_id").val(),
                date_from: $("#date_from").val(),
                date_to: $("#date_to").val(),
            };

            $.ajax({
                url: '/backend/index.php?r=checkRoom',
                method: 'POST',
                data: data,
                success: function (response) {
                    if (response.status === 'yes') {
                        notify(response.message);
                    } else if (response.status === 'no') {
                        notify(response.message, 'bg-danger');
                    }
                },
                error: function (error) {
                    console.log(error.responseJSON);
                }
            });
        })
    };

    return {
        init: function () {
            basicSetup();
            handleRooms();
            handleForm();
        }
    };
}();

$(document).ready(function () {
    Page.init();
});