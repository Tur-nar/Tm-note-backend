<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AetherNote Invitation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Main card -->
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; background-color: #111111; border: 1px solid #1f1f1f; border-radius: 16px; overflow: hidden;">

                    <!-- Header with gradient accent -->
                    <tr>
                        <td style="height: 4px; background: linear-gradient(90deg, #00ff41 0%, #00c853 50%, #69f0ae 100%);"></td>
                    </tr>

                    <!-- Logo -->
                    <tr>
                        <td style="padding: 32px 32px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-right: 8px;">
                                        <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #00ff41, #00c853); border-radius: 6px; display: inline-block;"></div>
                                    </td>
                                    <td style="font-size: 18px; font-weight: 700; color: #e0e0e0; letter-spacing: -0.3px;">
                                        AetherNote
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Title -->
                    <tr>
                        <td style="padding: 28px 32px 0;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; line-height: 1.3;">
                                @if($type === 'note')
                                    {{ $ownerName }} shared a note with you
                                @else
                                    {{ $ownerName }} invited you to their canvas
                                @endif
                            </h1>
                        </td>
                    </tr>

                    <!-- Note details card -->
                    @if($type === 'note' && $noteTitle)
                    <tr>
                        <td style="padding: 20px 32px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <div style="font-size: 11px; font-weight: 600; color: #888888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                            Note
                                        </div>
                                        <div style="font-size: 16px; font-weight: 600; color: #e0e0e0;">
                                            {{ $noteTitle }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Permission badge -->
                    <tr>
                        <td style="padding: 16px 32px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size: 13px; color: #999999;">
                                        Permission:
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <span style="
                                            display: inline-block;
                                            padding: 3px 10px;
                                            border-radius: 20px;
                                            font-size: 12px;
                                            font-weight: 600;
                                            text-transform: capitalize;
                                            @if($permission === 'view')
                                                background-color: rgba(0, 255, 65, 0.1);
                                                color: #00ff41;
                                                border: 1px solid rgba(0, 255, 65, 0.2);
                                            @elseif($permission === 'edit')
                                                background-color: rgba(59, 130, 246, 0.1);
                                                color: #60a5fa;
                                                border: 1px solid rgba(59, 130, 246, 0.2);
                                            @else
                                                background-color: rgba(168, 85, 247, 0.1);
                                                color: #c084fc;
                                                border: 1px solid rgba(168, 85, 247, 0.2);
                                            @endif
                                        ">{{ $permission }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Description -->
                    <tr>
                        <td style="padding: 20px 32px 0;">
                            <p style="margin: 0; font-size: 14px; color: #999999; line-height: 1.6;">
                                @if($permission === 'view')
                                    You can view this {{ $type }}. You won't be able to make changes.
                                @elseif($permission === 'edit')
                                    You can view and edit this {{ $type }}.
                                @else
                                    You have full admin access to this {{ $type }}, including the ability to re-share it.
                                @endif
                            </p>
                        </td>
                    </tr>

                    <!-- Action buttons -->
                    <tr>
                        <td style="padding: 28px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <!-- Accept button -->
                                    <td width="48%" align="center">
                                        <a href="{{ $acceptUrl }}" style="
                                            display: inline-block;
                                            width: 100%;
                                            padding: 12px 24px;
                                            background: linear-gradient(135deg, #00ff41, #00c853);
                                            color: #000000;
                                            font-size: 14px;
                                            font-weight: 700;
                                            text-decoration: none;
                                            border-radius: 8px;
                                            text-align: center;
                                            box-sizing: border-box;
                                        ">Accept</a>
                                    </td>
                                    <td width="4%"></td>
                                    <!-- Decline button -->
                                    <td width="48%" align="center">
                                        <a href="{{ $declineUrl }}" style="
                                            display: inline-block;
                                            width: 100%;
                                            padding: 12px 24px;
                                            background-color: transparent;
                                            color: #999999;
                                            font-size: 14px;
                                            font-weight: 600;
                                            text-decoration: none;
                                            border-radius: 8px;
                                            border: 1px solid #333333;
                                            text-align: center;
                                            box-sizing: border-box;
                                        ">Decline</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer divider -->
                    <tr>
                        <td style="padding: 0 32px;">
                            <div style="height: 1px; background-color: #1f1f1f;"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 32px 28px;">
                            <p style="margin: 0; font-size: 12px; color: #555555; line-height: 1.5;">
                                This invitation was sent to <span style="color: #888888;">{{ $share->shared_email }}</span>.
                                If you didn't expect this email, you can safely ignore it.
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- Sub-footer -->
                <p style="margin: 24px 0 0; font-size: 11px; color: #444444;">
                    &copy; {{ date('Y') }} AetherNote &middot; Your second brain
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
