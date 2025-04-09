# Cocoon Pipe - Gestionnaire de Middlewares PSR-15

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://php.net/)
[![PSR-15](https://img.shields.io/badge/PSR-15-blue.svg)](https://www.php-fig.org/psr/psr-15/)

Un gestionnaire de middlewares PSR-15 puissant et flexible pour PHP 8.0+, avec support des attributs PHP 8, du routage conditionnel et de la priorisation des middlewares.

## 🚀 Caractéristiques

- ✨ Compatible PSR-15
- 🎯 Support des attributs PHP 8
- 🛣️ Routage flexible avec support des expressions régulières et wildcards
- ⚡ Middlewares conditionnels
- 📊 Système de priorité
- 🔍 Débogage intégré avec Tracy
- 📝 Logging complet des opérations

## 📦 Installation

```bash
composer require cocoon-projet/pipe
```

## 🎯 Utilisation de base

```php
use Cocoon\Pipe\Pipe;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

// Création du pipeline
$pipe = new Pipe();

// Ajout de middlewares
$pipe->add(new SecurityMiddleware())
     ->add(new AuthMiddleware())
     ->add(new LoggerMiddleware());

// Vous pouvez aussi ajouter des middlewares via leur nom de classe
$pipe->add(App\Middlewares\SecurityMiddleware::class)
     ->add(App\Middlewares\AuthMiddleware::class)
     ->add(App\Middlewares\LoggerMiddleware::class);

// Traitement d'une requête
$response = $pipe->handle($request);
```

## 🛠️ Types de Middlewares

### 1. Middleware Simple

```php
class SimpleMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Traitement avant
        $response = $handler->handle($request);
        // Traitement après
        return $response;
    }
}
```

### 2. Middleware avec Priorité

```php
use Cocoon\Pipe\Attribute\Priority;

#[Priority(100)]
class HighPriorityMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request);
    }
}
```

### 3. Middleware avec Route

```php
use Cocoon\Pipe\Attribute\Route;

#[Route('api/*', methods: ['GET', 'POST'])]
class ApiMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request);
    }
}
```

### 4. Middleware Conditionnel

```php
use Cocoon\Pipe\Conditional\ConditionalMiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface, ConditionalMiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request);
    }

    public function shouldExecute(ServerRequestInterface $request): bool
    {
        return !$request->hasHeader('Authorization');
    }
}
```

## 🛣️ Patterns de Route Supportés

1. **Pattern Simple avec Wildcards**
```php
#[Route('api/*')]          // Correspond à /api/users, /api/posts, etc.
#[Route('public/**')]      // Correspond à tous les sous-chemins de public/
```

2. **Expression Régulière**
```php
#[Route('/^\/admin\/.*$/')]  // Correspond à tous les chemins commençant par /admin/
```

3. **Méthodes HTTP Spécifiques**
```php
#[Route('api/*', methods: ['GET', 'POST'])]
```

## 🔄 Ordre d'Exécution des Middlewares

Les middlewares sont exécutés selon les règles suivantes :

1. **Priorité** : Les middlewares sont triés par priorité croissante (0 par défaut)
   - Plus la valeur est basse, plus tôt le middleware sera exécuté
   - Utilisez l'attribut `#[Priority(value: int)]` pour définir la priorité

2. **Ordre d'ajout** : À priorité égale, l'ordre d'ajout est préservé
   - Premier ajouté = Premier exécuté (FIFO)
   - Cet ordre est maintenu naturellement par le système

3. **Conditions d'exécution** :
   - Les routes sont vérifiées pour chaque middleware
   - Les conditions personnalisées sont évaluées
   - Un middleware n'est exécuté que si toutes ses conditions sont remplies

## 🐛 Débogage avec Tracy

La bibliothèque intègre Tracy pour un débogage avancé :

```php
use Tracy\Debugger;

// Configuration de base
Debugger::enable(Debugger::DEVELOPMENT);
Debugger::$logDirectory = __DIR__ . '/logs';

// Configuration recommandée
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Nettoyage automatique des logs
$now = time();
$maxAge = 7 * 24 * 60 * 60; // 7 jours
foreach (glob($logDir . '/*') as $file) {
    if (is_file($file) && $now - filemtime($file) >= $maxAge) {
        unlink($file);
    }
}
```

## 🧪 Tests

```bash
# Exécution des tests
composer test

# Vérification du style de code
composer cs-check

# Correction automatique du style
composer cs-fix
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## 📝 Licence

MIT License. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🔍 Exemples Complets

Voir le fichier [examples/index.php](examples/index.php) pour des exemples complets d'utilisation.

## ⚠️ Notes Importantes

- Requiert PHP 8.0 ou supérieur
- Suit les standards PSR-15
- Les middlewares conditionnels doivent implémenter `ConditionalMiddlewareInterface`
- Les attributs de route et de priorité sont optionnels
- Les logs sont automatiquement nettoyés après 7 jours
- Les wildcards dans les routes sont non-gourmands par défaut
- Le système de priorité est optimisé pour maintenir l'ordre d'insertion
- Les middlewares peuvent être ajoutés via une instance ou via leur nom de classe complet

