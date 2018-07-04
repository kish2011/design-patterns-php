<?php

namespace RefactoringGuru\Mediator\RealWorld;

/**
 * EN: Mediator Design Pattern
 *
 * Intent: Define an object that encapsulates how a set of objects interact.
 * Mediator promotes loose coupling by keeping objects from referring to each
 * other explicitly, and it lets you vary their interaction independently.
 *
 * Example: In this example, the Mediator pattern expands the idea of the
 * Observer pattern by providing a centralized event dispatcher. It allows any
 * object to track & trigger events in other objects without depending on their
 * classes.
 *
 * RU: Паттерн Посредник
 *
 * Назначение: Определяет объект, который инкапсулирует взаимодействие набора объектов.
 * Посредник способствует свободной связи, удерживая объекты от обращения друг к другу
 * напрямую, и это позволяет вам менять их взаимодействие независимо. 
 *
 * Пример: В этом примере паттерн Посредника расширяет представление о паттерне Наблюдателя,
 * предоставляя централизованный диспетчер событий. Это позволяет любому объекту отслеживать
 * и запускать события в других объектах, не зависимо от их классов.
 */

/**
 * EN:
 * The Event Dispatcher class acts as a Mediator and contains the subscription
 * and notification logic. While a classic Mediator often depends on concrete
 * component classes, this one is only tied to their abstract interfaces.
 *
 * We are able to achieve this level of indirection thanks to the way the
 * connections between components are established. The components themselves may
 * subscribe to specific events that they are interested in via the Mediator's
 * subscription interface.
 *
 * Note, we can't use the PHP's built-in Subject/Observer interfaces here
 * because we'll be stretching them too far from what they were designed for.
 *
 * RU:
 * Класс Диспетчера Событий выполняет функции Посредника и содержит логику подписки
 * и уведомлений. Хотя классический Посредник часто зависит от конкретных классов
 * компонентов, этот привязан только к их абстрактным интерфейсам.
 *
 * Мы можем достичь этого уровня косвенности благодаря способу, которым установлены
 * связи между компонентами. Компоненты сами могут подписаться на интересующие их
 * конкретные события через интерфейс подписки Посредника.
 *
 * Обратите внимание, что мы не можем использовать здесь встроенные в PHP интерфейсы 
 * Subject/Observer, потому что мы будем растягивать их слишком далеко от того, 
 * для чего они были разработаны.
 */
class EventDispatcher
{
    /**
     * @var array
     */
    private $observers = [];

    public function __construct()
    {
        // EN: The special event group for observers that want to listen to all
        // events.
        //
        // RU: Специальная группа событий для наблюдателей, которые хотят слышать
        // все события.
        $this->observers["*"] = [];
    }

    private function initEventGroup(string &$event = "*")
    {
        if (! isset($this->observers[$event])) {
            $this->observers[$event] = [];
        }
    }

    private function getEventObservers(string $event = "*")
    {
        $this->initEventGroup($event);
        $group = $this->observers[$event];
        $all = $this->observers["*"];

        return array_merge($group, $all);
    }

    public function attach(Observer $observer, string $event = "*")
    {
        $this->initEventGroup($event);

        $this->observers[$event][] = $observer;
    }

    public function detach(Observer $observer, string $event = "*")
    {
        foreach ($this->getEventObservers($event) as $key => $s) {
            if ($s === $observer) {
                unset($this->observers[$event][$key]);
            }
        }
    }

    public function trigger(string $event, object $emitter, $data = null)
    {
        print("EventDispatcher: Broadcasting the '$event' event.\n");
        foreach ($this->getEventObservers($event) as $observer) {
            $observer->update($event, $emitter, $data);
        }
    }
}

/**
 * EN:
 * A simple helper function to provide global access to the event dispatcher.
 *
 * RU:
 * Простая вспомогательная функция для предоставления глобального доступа
 * к диспетчеру событий.
 */
function events(): EventDispatcher
{
    static $eventDispatcher;
    if (! $eventDispatcher) {
        $eventDispatcher = new EventDispatcher();
    }

    return $eventDispatcher;
}

/**
 * EN:
 * The Observer interface defines how components receive the event
 * notifications.
 *
 * RU:
 * Интерфейс Наблюдателя определяет, как компоненты получают уведомления
 * о событиях.
 */
interface Observer
{
    public function update(string $event, object $emitter, $data = null);
}

/**
 * EN:
 * Unlike our Observer pattern example, this example makes the UserRepository
 * act as a regular component that doesn't have any special event-related
 * methods. Like any other component, this class relies on the EventDispatcher
 * to broadcast its events and listen for the other ones.
 *
 * @see \RefactoringGuru\Observer\RealWorld\UserRepository
 *
 * RU:
 * В отличие от нашего примера паттерна Наблюдателя, этот пример заставляет
 * ПользовательскийРепозиторий действовать как обычный компонент, который не имеет
 * никаких специальных методов, связанных с событиями. Как и любой другой компонент,
 * этот класс использует ДиспетчерСобытий для трансляции своих событий и прослушивания
 * других.
 *
 * @see \RefactoringGuru\Observer\RealWorld\UserRepository
 */
class UserRepository implements Observer
{
    /**
     * EN:
     * @var array List of application's users.
     *
     * RU:
     * @var array Список пользователей приложения.
     */
    private $users = [];

    /**
     * EN:
     * Components can subscribe to events by themselves or by client code.
     *
     * RU:
     * Компоненты могут подписаться на события самостоятельно или через клиентский код.
     */
    public function __construct()
    {
        events()->attach($this, "users:deleted");
    }

    /**
     * EN:
     * Components can decide whether they'd like to process an event using its
     * name, emitter or any contextual data passed along with the event.
     *
     * RU:
     * Компоненты могут принять решение, будут ли они обрабатывать событие, используя его
     * название, источник или какие-то контекстные данные, переданные вместе с событием.
     */
    public function update(string $event, object $emitter, $data = null)
    {
        switch ($event) {
            case "users:deleted":
                if ($emitter === $this) {
                    return;
                }
                $this->deleteUser($data, true);
                break;
        }
    }

    // EN: These methods represent the business logic of the class.
    //
    // RU: Эти методы представляют бизнес-логику класса.

    public function initialize($filename)
    {
        print("UserRepository: Loading user records from a file.\n");
        // ...
        events()->trigger("users:init", $this, $filename);
    }

    public function createUser(array $data, $silent = false)
    {
        print("UserRepository: Creating a user.\n");

        $user = new User();
        $user->update($data);

        $id = bin2hex(openssl_random_pseudo_bytes(16));
        $user->update(["id" => $id]);
        $this->users[$id] = $user;

        if (! $silent) {
            events()->trigger("users:created", $this, $user);
        }

        return $user;
    }

    public function updateUser(User $user, array $data, $silent = false)
    {
        print("UserRepository: Updating a user.\n");

        $id = $user->attributes["id"];
        if (! isset($this->users[$id])) {
            return null;
        }

        $user = $this->users[$id];
        $user->update($data);

        if (! $silent) {
            events()->trigger("users:updated", $this, $user);
        }

        return $user;
    }

    public function deleteUser(User $user, $silent = false)
    {
        print("UserRepository: Deleting a user.\n");

        $id = $user->attributes["id"];
        if (! isset($this->users[$id])) {
            return;
        }

        unset($this->users[$id]);

        if (! $silent) {
            events()->trigger("users:deleted", $this, $user);
        }
    }
}

/**
 * EN:
 * Let's keep the User class trivial since it's not the focus of our example.
 *
 * RU:
 * 
 */
class User
{
    public $attributes = [];

    public function update($data)
    {
        $this->attributes = array_merge($this->attributes, $data);
    }

    /**
     * All objects can trigger events.
     */
    public function delete()
    {
        print("User: I can now delete myself without worrying about the repository.\n");
        events()->trigger("users:deleted", $this, $this);
    }
}

/**
 * This Concrete Component logs any events it's subscribed to.
 */
class Logger implements Observer
{
    private $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function update(string $event, object $emitter, $data = null)
    {
        $entry = date("Y-m-d H:i:s").": '$event' with data '".json_encode($data)."'\n";
        file_put_contents($this->filename, $entry, FILE_APPEND);

        print("Logger: I've written '$event' entry to the log.\n");
    }
}

/**
 * This Concrete Component sends initial instructions to new users. The client
 * is responsible for attaching this component to a proper user creation event.
 */
class OnboardingNotification implements Observer
{
    private $adminEmail;

    public function __construct($adminEmail)
    {
        $this->adminEmail = $adminEmail;
    }

    public function update(string $event, object $emitter, $data = null)
    {
        // mail($this->adminEmail,
        //     "Onboarding required",
        //     "We have a new user. Here's his info: " .json_encode($data));

        print("OnboardingNotification: The notification has been emailed!\n");
    }
}

/**
 * The client code.
 */

$repository = new UserRepository();
events()->attach($repository, "facebook:update");

$logger = new Logger(__DIR__ . "/log.txt");
events()->attach($logger, "*");

$onboarding = new OnboardingNotification("1@example.com");
events()->attach($onboarding, "users:created");

// ...

$repository->initialize(__DIR__ . "users.csv");

// ...

$user = $repository->createUser([
    "name" => "John Smith",
    "email" => "john99@example.com",
]);

// ...

$user->delete();
