# HasRatingAttributes - Référence Technique

## Description

Fournit des accesseurs Eloquent typés pour récupérer les informations d'évaluation (notes, nombre d'avis, distribution) sur n'importe quel modèle Eloquent.

## Hiérarchie

```
Trait
    └── HasRatingAttributes
```

## Rôle principal

Ce trait ajoute quatre attributs Eloquent à un modèle, permettant d'accéder facilement aux données d'évaluation sans duplication de code. Il s'appuie sur le package **Laravel Ratings** pour effectuer les calculs.

Les attributs disponibles sont :
- `average_rating` : Note moyenne
- `rating_count` : Nombre total d'avis
- `rating_distribution` : Distribution des notes par niveau (1 à 5)
- `has_ratings` : Indicateur de présence d'avis

## API / Méthodes publiques

### `averageRating(): Attribute`

Retourne un attribut Eloquent qui calcule la note moyenne du modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<float>` - Attribut Eloquent retournant un flottant

**Exceptions :** Aucune (les erreurs sont capturées et retournent 0.0)

**Exemple :**
```php
$post = Post::find(1);
$average = $post->average_rating; // float: 4.5
```

---

### `ratingCount(): Attribute`

Retourne un attribut Eloquent qui compte le nombre total d'évaluations du modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<int>` - Attribut Eloquent retournant un entier

**Exceptions :** Aucune (les erreurs sont capturées et retournent 0)

**Exemple :**
```php
$post = Post::find(1);
$count = $post->rating_count; // int: 42
```

---

### `ratingDistribution(): Attribute`

Retourne un attribut Eloquent qui fournit la distribution des notes par niveau (1 à 5).

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<array<int, int>>` - Attribut Eloquent retournant un tableau associatif

**Exceptions :** Aucune (les erreurs sont capturées et retournent un tableau vide)

**Exemple :**
```php
$post = Post::find(1);
$distribution = $post->rating_distribution;
// array: [1 => 2, 2 => 5, 3 => 10, 4 => 32, 5 => 78]
```

---

### `hasRatings(): Attribute`

Retourne un attribut Eloquent qui indique si le modèle possède au moins une évaluation.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `Attribute<bool>` - Attribut Eloquent retournant un booléen

**Exceptions :** Aucune (les erreurs sont capturées et retournent false)

**Exemple :**
```php
$post = Post::find(1);
if ($post->has_ratings) {
    // Afficher les évaluations
}
```

---

### `isRateable(): bool`

Méthode protégée déterminant si le modèle peut être évalué. À surcharger dans le modèle pour ajouter des conditions personnalisées.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `bool` - True par défaut

**Exceptions :** Aucune

**Exemple :**
```php
// Dans le modèle
protected function isRateable(): bool
{
    return $this->status === 'published' && !$this->trashed();
}
```

## Cas d'utilisation

### Cas 1 : Affichage des évaluations sur une fiche produit

**Problème :** Afficher les évaluations d'un produit avec la note moyenne, le nombre d'avis et la distribution.

**Solution :** Utiliser les attributs du trait dans la vue ou l'API.

```php
// Dans le contrôleur
$product = Product::find(1);

return response()->json([
    'name' => $product->name,
    'average_rating' => $product->average_rating,
    'rating_count' => $product->rating_count,
    'rating_distribution' => $product->rating_distribution,
    'has_ratings' => $product->has_ratings,
]);
```

### Cas 2 : Filtrage des modèles avec évaluations

**Problème :** Récupérer uniquement les produits qui ont des évaluations.

**Solution :** Utiliser `has_ratings` dans les conditions.

```php
$products = Product::all()->filter(fn($product) => $product->has_ratings);
```

### Cas 3 : Surcharge de `isRateable()` pour des conditions métier

**Problème :** Un médecin ne doit être évaluable que s'il est actif et vérifié.

**Solution :** Surcharger `isRateable()` dans le modèle.

```php
// Dans le modèle Doctor
protected function isRateable(): bool
{
    return $this->is_active && $this->is_verified && $this->user_type->isDoctor();
}

// Utilisation
$doctor = Doctor::find(1);
$average = $doctor->average_rating; // Retourne 0.0 si non rateable
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Erreur lors du calcul (service indisponible) | Capturée | Retourne la valeur par défaut (0, 0.0, [], false) |
| Modèle non rateable | - | Retourne la valeur par défaut |
| Exception générale | Capturée | Retourne la valeur par défaut |

**Note :** Toutes les exceptions sont capturées en interne pour éviter que l'application ne plante. Les valeurs par défaut sont retournées silencieusement.

## Intégration

### Avec Laravel Ratings

Ce trait utilise le `RatingService` du package `andydefer/laravel-ratings` pour effectuer tous les calculs.

```php
$ratingService = app(RatingService::class);
$ratingService->getAverageRating($this);
$ratingService->countRatings($this);
$ratingService->getRatingDistribution($this);
```

### Avec un modèle Eloquent

```php
use AndyDefer\Mixins\Traits\HasRatingAttributes;

final class Doctor extends Model
{
    use HasRatingAttributes;

    protected function isRateable(): bool
    {
        return $this->is_active && $this->user_type->isDoctor();
    }
}
```

## Performance

- **Mise en cache :** Les attributs sont calculés à chaque accès. Pour des performances optimales, envisager un cache :
```php
public function averageRating(): Attribute
{
    return Attribute::make(
        get: function (): float {
            return Cache::remember(
                "rating_average_{$this->id}",
                3600,
                fn() => app(RatingService::class)->getAverageRating($this)
            );
        }
    );
}
```

- **Requêtes :** `countRatings()` effectue une requête COUNT en base de données. `getRatingDistribution()` effectue une requête GROUP BY. Pour les grandes collections, privilégier le chargement paresseux.

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| PHP 7.4 | ❌ Non supporté (nécessite PHP 8.0+) |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\Mixins\Traits\HasRatingAttributes;
use Illuminate\Database\Eloquent\Model;

final class Product extends Model
{
    use HasRatingAttributes;

    protected $fillable = ['name', 'description', 'is_active'];

    protected function isRateable(): bool
    {
        return $this->is_active;
    }
}

// Création d'un produit avec évaluations
$product = Product::create([
    'name' => 'Laptop Pro',
    'description' => 'High-end laptop',
    'is_active' => true,
]);

// Ajout d'évaluations (via Laravel Ratings)
$ratingService = app(RatingService::class);
$user = User::find(1);

$ratingService->rate($user, $product, RatingLevel::FIVE, 'Excellent!');
$ratingService->rate(User::find(2), $product, RatingLevel::FOUR, 'Good');
$ratingService->rate(User::find(3), $product, RatingLevel::FIVE, 'Amazing!');

// Récupération des données
echo $product->average_rating;      // 4.67
echo $product->rating_count;        // 3
print_r($product->rating_distribution); // [1=>0, 2=>0, 3=>0, 4=>1, 5=>2]
var_dump($product->has_ratings);    // true
```

## Voir aussi

- `HasAvailabilityAttributes` - Attributs de disponibilité
- `Laravel Ratings` - Package d'évaluation
- `RatingService` - Service d'évaluation
- `RatingLevel` - Enum des niveaux d'évaluation