<h1 align="center"><!-- NAME_START -->PHP EventBus Library<!-- NAME_END --></h1>

<!-- BADGES_START -->
<p align="center">
    <strong>TeamSquad PHP EventBus library package.</strong>
</p>

<p align="center">
    <a href="https://github.com/teamsquad-io/php-event-bus">
        <img src="https://img.shields.io/badge/source-teamsquad--io/php--event--bus-blue.svg?style=flat-square" alt="Source Code">
    </a>
    <a href="https://packagist.org/packages/teamsquad.io/php-event-bus">
        <img src="https://img.shields.io/packagist/v/teamsquad.io/php-event-bus.svg?style=flat-square&label=release" alt="Download Package">
    </a>
    <a href="https://php.net">
        <img src="https://img.shields.io/packagist/php-v/teamsquad.io/php-event-bus.svg?style=flat-square&colorB=%238892BF" alt="PHP Programming Language">
    </a>
    <a href="https://github.com/teamsquad-io/php-event-bus/actions/workflows/ci.yml">
        <img src="https://img.shields.io/github/workflow/status/teamsquad-io/php-event-bus/CI?logo=github" alt="Build Status">
    </a>
    <a href="https://codecov.io/gh/teamsquad-io/php-event-bus">
        <img src="https://img.shields.io/codecov/c/gh/teamsquad-io/php-event-bus?label=codecov&logo=codecov&style=flat-square" alt="Codecov Code Coverage">
    </a>
    <a href="https://shepherd.dev/github/teamsquad-io/php-event-bus">
        <img src="https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fshepherd.dev%2Fgithub%2Fteamsquad-io%2Fphp-event-bus%2Fcoverage" alt="Psalm Type Coverage">
    </a>
</p>
<!-- BADGES_END -->
This is the TeamSquad PHP EventBus Library. It's used to publish and listen to events.

<h2>Installation</h2>
<p>
    <code>composer require teamsquad/php-event-bus</code>
</p>

## Development

### Initialization

```bash
composer install
```

### Run composer scripts

```bash
composer test-all     # run test-quality & test-phpunit
composer test-quality # run csrun & psalm & phpstan
composer test-phpunit # run phpunit

composer csrun   # check code style
composer psalm   # run psalm coverage
composer phpstan # run phpstan coverage
```

### Git hooks

Install the pre-commit hook running:

```bash
./tools/git-hooks/init.sh
```

### Basic Dockerfile

If you don't have PHP in your local machine, you can use docker to build an image with `PHP 8.0`.

```bash
docker build -t php-event-bus .
```

## Contributing

Feel free to open any PR with your ideas, suggestions or improvements.

