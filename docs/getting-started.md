# Getting Started with PHP EventBus

## Installation

Install the package via Composer:

```bash
composer require teamsquad.io/php-event-bus
```

## Quick Start

### 1. Create an Event

Events represent something that has happened in your application:

```php
<?php

namespace App\Events;

use TeamSquad\EventBus\Domain\Event;

class UserRegistered implements Event
{
    public function __construct(
        private string $userId,
        private string $email,
        private \DateTimeImmutable $registeredAt
    ) {}

    public function eventName(): string
    {
        return 'app.users.registered';
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'registered_at' => $this->registeredAt->format('c'),
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['user_id'],
            $array['email'],
            new \DateTimeImmutable($array['registered_at'])
        );
    }

    // Getters
    public function getUserId(): string { return $this->userId; }
    public function getEmail(): string { return $this->email; }
    public function getRegisteredAt(): \DateTimeImmutable { return $this->registeredAt; }
}
```

### 2. Create a Consumer

Consumers listen to events and perform actions:

```php
<?php

namespace App\Consumers;

use App\Events\UserRegistered;
use TeamSquad\EventBus\Domain\Consumer;

class WelcomeEmailConsumer implements Consumer
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function actionIndex(): void
    {
        // This method is called by the event bus framework
        // The actual event handling is done in the listen method
    }

    public function listenUserRegistered(UserRegistered $event): void
    {
        $this->emailService->sendWelcomeEmail(
            $event->getEmail(),
            $event->getUserId()
        );
    }
}
```

### 3. Setup RabbitMQ Connection

Configure your RabbitMQ connection:

```php
<?php

use TeamSquad\EventBus\Infrastructure\Rabbit;
use TeamSquad\EventBus\Infrastructure\RabbitBus;
use TeamSquad\EventBus\Infrastructure\SimpleEncrypt;
use TeamSquad\EventBus\Infrastructure\SystemClock;
use TeamSquad\EventBus\Domain\Secrets;

// Create secrets implementation
$secrets = new class implements Secrets {
    public function get(string $key): string {
        return match($key) {
            'rabbit_host' => 'localhost',
            'rabbit_port' => '5672',
            'rabbit_user' => 'guest',
            'rabbit_pass' => 'guest',
            'rabbit_vhost' => '/',
            default => throw new InvalidArgumentException("Unknown secret: $key")
        };
    }
};

// Create the event bus
$rabbit = Rabbit::getInstance($secrets);
$encryption = new SimpleEncrypt();
$clock = new SystemClock();
$eventBus = new RabbitBus($rabbit, $encryption, $clock);
```

### 4. Publishing Events

Publish events when something happens:

```php
<?php

use TeamSquad\EventBus\Domain\EventCollection;
use App\Events\UserRegistered;

// Create and publish an event
$event = new UserRegistered(
    'user-123',
    'john@example.com',
    new \DateTimeImmutable()
);

$events = new EventCollection([$event]);
$eventBus->publish('user-events', $events);
```

### 5. Running Consumers

To run consumers, you'll need to set up a consumer configuration and use a message consumer framework. This typically involves:

1. Generating consumer configuration
2. Setting up worker processes
3. Handling message processing

## Docker Setup

For development, you can use Docker to run RabbitMQ:

```yaml
# docker-compose.yml
version: '3.8'
services:
  rabbitmq:
    image: rabbitmq:3-management
    ports:
      - "5672:5672"
      - "15672:15672"
    environment:
      RABBITMQ_DEFAULT_USER: guest
      RABBITMQ_DEFAULT_PASS: guest
    volumes:
      - rabbitmq_data:/var/lib/rabbitmq

volumes:
  rabbitmq_data:
```

Start RabbitMQ:

```bash
docker-compose up -d rabbitmq
```

## Configuration

### Consumer Configuration

The library can automatically generate consumer configurations based on your consumer classes:

```php
<?php

use TeamSquad\EventBus\Infrastructure\ConsumerConfigGenerator;
use TeamSquad\EventBus\Infrastructure\AutoloadConfig;

$config = new AutoloadConfig(
    whiteList: ['App\\Consumers\\'],
    blackList: []
);

$generator = new ConsumerConfigGenerator($config);
$consumerConfig = $generator->generate([
    'App\\Consumers\\WelcomeEmailConsumer' => '/path/to/file.php'
]);
```

### Encryption for Sensitive Events

For events containing sensitive data, implement the `EncryptedEvent` interface:

```php
<?php

namespace App\Events;

use TeamSquad\EventBus\Domain\Event;
use TeamSquad\EventBus\Domain\EncryptedEvent;

class UserPasswordChanged implements Event, EncryptedEvent
{
    public function __construct(
        private string $userId,
        private string $hashedPassword
    ) {}

    public function eventName(): string
    {
        return 'app.users.password_changed';
    }

    public static function protectedFields(): array
    {
        return ['hashed_password']; // This field will be encrypted
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'hashed_password' => $this->hashedPassword,
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['user_id'],
            $array['hashed_password']
        );
    }
}
```

## Best Practices

### Event Design
- Keep events immutable
- Include all necessary data in the event
- Use clear, descriptive event names
- Version your events for backward compatibility

### Consumer Design
- Keep consumers idempotent
- Handle failures gracefully
- Use dependency injection for services
- Keep business logic in services, not consumers

### Error Handling
- Implement proper logging
- Use dead letter queues for failed messages
- Consider retry strategies for transient failures
- Monitor consumer health and performance

## Next Steps

- Read the [Configuration Guide](configuration.md) for advanced setup
- Check the [API Reference](api/index.md) for detailed documentation
- See [Examples](examples/) for more complex use cases
- Learn about [Testing](testing.md) your event-driven code

## Troubleshooting

### Common Issues

**Connection refused to RabbitMQ**
- Ensure RabbitMQ is running
- Check connection credentials
- Verify network connectivity

**Events not being consumed**
- Check consumer configuration
- Verify queue bindings
- Check consumer process is running

**Permission errors**
- Verify RabbitMQ user permissions
- Check file system permissions for logs

For more help, see the [Troubleshooting Guide](troubleshooting.md) or open an issue on GitHub.