# hyperf-auth-guard

An authentication library for hyperf

## Features

- [x] Form Login auth
- [x] JSON login auth
- [x] API Key auth
- [ ] Opaque Token auth
- [ ] JWT auth
- [ ] OAuth2
- [ ] TOTP auth
- [ ] WebAuthn auth
- [ ] 2FA


-------

ServiceBuilder 处理方案
需要支持用户自定义扩展加载自定义组件

```
$configLoader = new ConfigLoader();
$config = $configLoader->load();

$serviceBuilder = new ServiceBuilder($config, $this->container);
$serviceBuilder->addProvider(new AuthProvider());

$serviceBuilder->build() {
    foreach ($this->serviceProviders as $serviceProvider) {
        $serviceProvider->register($config, $this->container);
        $serviceProvider->boot($config, $this->container);
    }
}

boot(Config) {

}
```

------

扩展机制
