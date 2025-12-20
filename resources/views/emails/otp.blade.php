<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác nhận</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h2 style="color: #2f855a; text-align: center;">Hệ Thống Đặt Lịch Khám</h2>

        <p>Xin chào,</p>
        <p>Bạn vừa yêu cầu lấy lại mật khẩu. Đây là mã xác nhận (OTP) của bạn:</p>

        <div style="text-align: center; margin: 30px 0;">
            <span
                style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #2f855a; background: #f0fff4; padding: 10px 20px; border: 1px dashed #2f855a; border-radius: 5px;">
                {{ $otp }}
            </span>
        </div>

        <p style="color: #666; font-size: 14px;">Mã này sẽ hết hạn sau 10 phút. Vui lòng không chia sẻ mã này cho bất kỳ
            ai.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="text-align: center; color: #999; font-size: 12px;">Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email
            này.</p>
    </div>
</body>

</html>