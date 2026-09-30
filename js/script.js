$(document).ready(function () {

    // ================================
    // PAGE FADE IN
    // ================================

    $("body").hide().fadeIn(700);


    // ================================
    // NAVIGATION HOVER EFFECT
    // ================================

    $(".main-nav a").hover(
        function () {
            $(this).stop().animate({
                opacity: 0.6
            }, 150);
        },
        function () {
            $(this).stop().animate({
                opacity: 1
            }, 150);
        }
    );


    // ================================
    // HERO BUTTON EFFECT
    // ================================

    $(".hero-button").hover(
        function () {
            $(this).stop().animate({
                paddingLeft: "28px",
                paddingRight: "28px"
            }, 200);
        },
        function () {
            $(this).stop().animate({
                paddingLeft: "22px",
                paddingRight: "22px"
            }, 200);
        }
    );


    // ================================
    // INFO BOX HOVER
    // ================================

    $(".info-box").hover(
        function () {
            $(this).stop().animate({
                paddingTop: "24px"
            }, 200);
        },
        function () {
            $(this).stop().animate({
                paddingTop: "20px"
            }, 200);
        }
    );


    // ================================
    // SMOOTH SCROLL
    // ================================

    $('a[href^="#"]').click(function (event) {

        var target = $(this.getAttribute("href"));

        if (target.length) {

            event.preventDefault();

            $("html, body").animate({
                scrollTop: target.offset().top
            }, 700);
        }
    });

    // ================================
// REGISTRATION FORM
// ================================

$("#registrationForm").submit(function (event) {

    event.preventDefault();

    var fullName = $("#fullName").val().trim();
    var studentId = $("#studentId").val().trim();
    var email = $("#email").val().trim();
    var phone = $("#phone").val().trim();
    var gender = $("#gender").val();
    var roomType = $("#roomType").val();
    var address = $("#address").val().trim();


    // Check empty fields
    if (
        fullName === "" ||
        studentId === "" ||
        email === "" ||
        phone === "" ||
        gender === null ||
        roomType === null ||
        address === ""
    ) {

        alert("Please fill all the required fields.");
        return;
    }


    // Phone validation
    var phonePattern = /^[0-9]{10}$/;

    if (!phonePattern.test(phone)) {

        alert("Please enter a valid 10-digit phone number.");
        return;
    }


    // Email validation
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailPattern.test(email)) {

        alert("Please enter a valid email address.");
        return;
    }


    // Get existing registrations
    var registrations =
        JSON.parse(localStorage.getItem("hostelRegistrations")) || [];


    // Check duplicate Student ID
    var alreadyRegistered = registrations.some(function (student) {

        return student.studentId.toLowerCase() === studentId.toLowerCase();

    });


    if (alreadyRegistered) {

        alert("This Student ID is already registered.");
        return;
    }


    // Create student object
    var student = {

        fullName: fullName,

        studentId: studentId,

        email: email,

        phone: phone,

        gender: gender,

        roomType: roomType,

        address: address,

        registrationDate: new Date().toLocaleDateString()

    };


    // Save registration
    registrations.push(student);

    localStorage.setItem(
        "hostelRegistrations",
        JSON.stringify(registrations)
    );


    // Show success message
    $("#successMessage")
        .stop()
        .fadeIn(400);


    // Clear form
    $("#registrationForm")[0].reset();


    // Hide message after 5 seconds
    setTimeout(function () {

        $("#successMessage").fadeOut(400);

    }, 5000);

});

});