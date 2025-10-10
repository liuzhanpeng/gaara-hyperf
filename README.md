# gaara-hyperf

An authentication library for hyperf

## Features

- [x] Form Login Authentication
- [x] JSON login Authentication
- [x] API Key Authentication
- [x] Opaque Token Authentication (with IP Binding/UA binding)
- [x] API Signature Authentication
- [ ] Magic Link Authentication
- [ ] JWT Authentication (with BlackList)
- [ ] OAuth 2.0/OpenID Connect
- [ ] TOTP Authentication
- [ ] WebAuthn Authentication
- [ ] 2FA
- [x] IP WhiteList
- [x] Limiting Login Attempts
- [ ] Password expiration
- [ ] User Disabled
- [ ] Single device login

-------

2fa流程
    - 进行第一次登录认证
    - 认证成功后生成一个TwoFactorToken
    - 跳转到2fa-challenge页面
    - 输入TOTP码, 提交到TwoFactorAuthenticator
    - 认证成功后，保存AuthenticatedToken