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

## Quick Start

Get started quickly with our [Getting Started Guide](docs/getting-started.md).

## Documentation

- [Getting Started Guide](docs/getting-started.md) - Complete setup and basic usage
- [Improvement Suggestions](IMPROVEMENTS.md) - Comprehensive improvement roadmap
- [Task List](TASKS.md) - Actionable development tasks

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


## Consumer configuration properties

This library generates and consumes RabbitMQ configurations for your consumers. Each consumer entry contains the following properties:

- amqp: Name of the AMQP connection profile to use (e.g., default, users).
- name: Human/unique identifier for the consumer, typically FQCN::method.
- routing_key: List of routing keys the queue is bound to (e.g., sample_event).
- unique: Whether this consumer definition should be treated as non-duplicated across generation/deployment.
- url: HTTP route that maps to a controller endpoint for this consumer.
- queue: Name of the queue to consume from (created/bound according to params when create_queue is true).
- exchange: Exchange name to bind the queue to (e.g., teamsquad.event_bus).
- function: Method on the consumer class that will be invoked for each message.
- create_queue: If true, the queue will be declared/created automatically when setting up the consumer.
- workers: Number of worker processes/consumers to spawn for this consumer (parallelism level).
- params: RabbitMQ queue declaration parameters used when creating/declaring the queue:
  - passive: If true, do not create; only check that the queue exists.
  - durable: If true, the queue will survive a broker restart.
  - exclusive: If true, the queue is restricted to this connection and will be deleted when the connection closes.
  - auto_delete: If true, the queue will be deleted when the last consumer unsubscribes.
  - nowait: If true, do not wait for a server response to the declare/bind operation.
  - args: Additional arguments for the queue declaration:
    - x-expires (type: int): Message expires (auto-deletes) after this many milliseconds of inactivity.
    - x-ha-policy (type: string): High-availability policy (e.g., all) for classic mirrored queues (legacy RabbitMQ).

Example configuration (high throughput):

```
[
    'amqp'         => 'default',
    'name'         => 'TeamSquad\Tests\SampleConsumerWithWorkers::listenSampleHighThroughputEvent',
    'routing_key'  => ['high_throughput_event'],
    'unique'       => false,
    'url'          => '/_/tests-sampleconsumerwithworkers',
    'queue'        => 'high.throughput.queue',
    'exchange'     => 'teamsquad.event_bus',
    'function'     => 'listenSampleHighThroughputEvent',
    'create_queue' => true,
    'workers'      => 10,
    'params'       => [
        'passive'     => false,
        'durable'     => false,
        'exclusive'   => false,
        'auto_delete' => false,
        'nowait'      => false,
        'args'        => [
            'x-expires'   => ['type' => 'int', 'val' => 300000],
            'x-ha-policy' => ['type' => 'string', 'val' => 'all'],
        ],
    ],
]
```
