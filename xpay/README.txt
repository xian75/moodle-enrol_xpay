xPay Enrolment Plugin
====================

Simple enrolment plugin for Moodle 2.x using the xPay "CartaSi" credit
card payment gateway.

- © Copyright 2013 Cristian Natale <cristian.natale@coorad.org>

- License: GNU GPL v3 or later - http://www.gnu.org/copyleft/gpl.html

DESCRIPTION
-----------

This plugin handles credit card payments by conducting PxPay transactions
through the xPay CartaSi gateway. A successful payment results in the
enrolment of the user. We use PxPay because it does not require handling the
credit card details in Moodle. A truncated form of the credit card number is
returned in the PxPay response and is stored for reference only.

Details of the xPay API are online:
http://www.paymentexpress.com/technical_resources/ecommerce_hosted/pxpay.html

INSTALLATION
------------

Download the latest enrol_xpay.zip from the Moodle Plugins Directory and unzip
the contents into the enrol/xpay directory.

- http://moodle.org/plugins/

SUPPORT
-------

Please visit the Moodle forums at http://moodle.org/forums/ and search for xpay
to see if any relevant help has already been posted, or post a new question.

