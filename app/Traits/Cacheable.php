<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

trait Cacheable
{
    /**
     * Durée du cache par défaut (en secondes)
     * 1 heure = 3600 secondes
     */
    protected int $defaultCacheDuration = 3600;

    /**
     * Préfixe pour les clés de cache
     */
    protected function getCachePrefix(): string
    {
        return strtolower(class_basename($this)) . ':';
    }

    /**
     * Récupérer ou mettre en cache une donnée
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl Durée de vie en secondes
     * @return mixed
     */
    protected function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $cacheKey = $this->getCachePrefix() . $key;
        $ttl = $ttl ?? $this->defaultCacheDuration;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Récupérer ou mettre en cache pour toujours
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    protected function rememberForever(string $key, callable $callback)
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::rememberForever($cacheKey, $callback);
    }

    /**
     * Mettre une valeur en cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    protected function putCache(string $key, $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->getCachePrefix() . $key;
        $ttl = $ttl ?? $this->defaultCacheDuration;

        return Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Récupérer une valeur du cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getCache(string $key, $default = null)
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::get($cacheKey, $default);
    }

    /**
     * Vérifier si une clé existe dans le cache
     *
     * @param string $key
     * @return bool
     */
    protected function hasCache(string $key): bool
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::has($cacheKey);
    }

    /**
     * Supprimer une valeur du cache
     *
     * @param string $key
     * @return bool
     */
    protected function forgetCache(string $key): bool
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::forget($cacheKey);
    }

    /**
     * Vider tout le cache d'un modèle
     *
     * @return bool
     */
    protected function flushCache(): bool
    {
        $pattern = $this->getCachePrefix() . '*';
        
        try {
            // Si Redis est disponible, utiliser la méthode optimisée
            if (config('cache.default') === 'redis' && class_exists('Redis')) {
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        // Enlever le préfixe Redis automatique si présent
                        $cleanKey = str_replace(config('database.redis.options.prefix'), '', $key);
                        Cache::forget($cleanKey);
                    }
                }
                
                return true;
            } else {
                // Fallback : utiliser Cache::flush()
                return Cache::flush();
            }
        } catch (\Exception $e) {
            // Si Redis n'est pas disponible, utiliser Cache::flush()
            try {
                return Cache::flush();
            } catch (\Exception $flushException) {
                return false;
            }
        }
    }

    /**
     * Récupérer une liste paginée avec cache
     *
     * @param string $key
     * @param int $page
     * @param int $perPage
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    protected function rememberPaginated(string $key, int $page, int $perPage, callable $callback, ?int $ttl = null)
    {
        $cacheKey = $key . ':page:' . $page . ':perPage:' . $perPage;
        
        return $this->remember($cacheKey, $callback, $ttl);
    }

    /**
     * Invalider le cache paginé pour une clé
     *
     * @param string $key
     * @return bool
     */
    protected function forgetPaginatedCache(string $key): bool
    {
        $pattern = $this->getCachePrefix() . $key . ':page:*';
        
        try {
            // Si Redis est disponible, utiliser la méthode optimisée
            if (config('cache.default') === 'redis' && class_exists('Redis')) {
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $cacheKey) {
                        $cleanKey = str_replace(config('database.redis.options.prefix'), '', $cacheKey);
                        Cache::forget($cleanKey);
                    }
                }
            } else {
                // Fallback : vider simplement le cache complet (non optimal mais fonctionne)
                Cache::flush();
            }
            
            return true;
        } catch (\Exception $e) {
            // En cas d'erreur, essayer de flush le cache
            try {
                Cache::flush();
            } catch (\Exception $flushException) {
                // Ignorer silencieusement
            }
            return false;
        }
    }

    /**
     * Récupérer avec cache et tags (pour grouper les clés)
     *
     * @param array $tags
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    protected function rememberWithTags(array $tags, string $key, callable $callback, ?int $ttl = null)
    {
        $cacheKey = $this->getCachePrefix() . $key;
        $ttl = $ttl ?? $this->defaultCacheDuration;

        return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Invalider le cache par tags
     *
     * @param array $tags
     * @return bool
     */
    protected function flushCacheTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Incrémenter une valeur dans le cache
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    protected function incrementCache(string $key, int $value = 1)
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::increment($cacheKey, $value);
    }

    /**
     * Décrémenter une valeur dans le cache
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    protected function decrementCache(string $key, int $value = 1)
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::decrement($cacheKey, $value);
    }

    /**
     * Obtenir plusieurs valeurs du cache
     *
     * @param array $keys
     * @return array
     */
    protected function getMultipleCache(array $keys): array
    {
        $cacheKeys = array_map(function ($key) {
            return $this->getCachePrefix() . $key;
        }, $keys);

        return Cache::many($cacheKeys);
    }

    /**
     * Mettre plusieurs valeurs en cache
     *
     * @param array $values Format: ['key' => 'value', ...]
     * @param int|null $ttl
     * @return bool
     */
    protected function putMultipleCache(array $values, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultCacheDuration;
        
        $cacheValues = [];
        foreach ($values as $key => $value) {
            $cacheValues[$this->getCachePrefix() . $key] = $value;
        }

        return Cache::putMany($cacheValues, $ttl);
    }

    /**
     * Récupérer et supprimer une valeur du cache
     *
     * @param string $key
     * @return mixed
     */
    protected function pullCache(string $key)
    {
        $cacheKey = $this->getCachePrefix() . $key;

        return Cache::pull($cacheKey);
    }

    /**
     * Ajouter une valeur au cache seulement si elle n'existe pas
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    protected function addCache(string $key, $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->getCachePrefix() . $key;
        $ttl = $ttl ?? $this->defaultCacheDuration;

        return Cache::add($cacheKey, $value, $ttl);
    }
}
