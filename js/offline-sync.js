/**
 * Service de synchronisation hors-ligne côté client
 * Utilise IndexedDB pour stocker les données hors-ligne
 */

class OfflineSyncClient {
    constructor(dbName = 'ADH_Offline', storeName = 'sync_queue') {
        this.dbName = dbName;
        this.storeName = storeName;
        this.db = null;
        this.isOnline = navigator.onLine;
        this.initDB();
        this.setupListeners();
    }
    
    /**
     * Initialise la base de données IndexedDB
     */
    initDB() {
        const request = indexedDB.open(this.dbName, 1);
        
        request.onerror = () => {
            console.error('Erreur : impossible d\'ouvrir IndexedDB');
        };
        
        request.onsuccess = (event) => {
            this.db = event.target.result;
            console.log('IndexedDB initialisée');
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(this.storeName)) {
                const store = db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                store.createIndex('status', 'status', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    }
    
    /**
     * Configure les événements de connectivité
     */
    setupListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('Connecté au réseau');
            this.syncPendingActions();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('Déconnecté du réseau');
        });
    }
    
    /**
     * Ajoute une action à la file d'attente
     */
    async queueAction(action) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            
            const item = {
                ...action,
                status: 'pending',
                timestamp: Date.now(),
                attempts: 0
            };
            
            const request = store.add(item);
            
            request.onsuccess = () => {
                console.log('Action ajoutée à la file d\'attente:', item);
                if (this.isOnline) {
                    this.syncAction(request.result);
                }
                resolve(request.result);
            };
            
            request.onerror = () => {
                reject(new Error('Erreur lors de l\'ajout à la file d\'attente'));
            };
        });
    }
    
    /**
     * Synchronise une action individuelle
     */
    async syncAction(actionId) {
        const action = await this.getAction(actionId);
        if (!action || action.status === 'synced') return;
        
        try {
            const response = await fetch('api/sync.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(action)
            });
            
            if (response.ok) {
                this.markAsSynced(actionId);
                console.log('Action synchronisée:', actionId);
            } else {
                this.incrementAttempts(actionId);
            }
        } catch (error) {
            console.error('Erreur de synchronisation:', error);
            this.incrementAttempts(actionId);
        }
    }
    
    /**
     * Synchronise toutes les actions en attente
     */
    async syncPendingActions() {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const index = store.index('status');
            const range = IDBKeyRange.only('pending');
            const request = index.getAll(range);
            
            request.onsuccess = async (event) => {
                const actions = event.target.result;
                
                for (const action of actions) {
                    await this.syncAction(action.id);
                }
                
                resolve();
            };
            
            request.onerror = () => {
                reject(new Error('Erreur lors de la lecture de la file d\'attente'));
            };
        });
    }
    
    /**
     * Récupère une action
     */
    async getAction(actionId) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const request = store.get(actionId);
            
            request.onsuccess = () => {
                resolve(request.result);
            };
            
            request.onerror = () => {
                reject(new Error('Erreur lors de la lecture'));
            };
        });
    }
    
    /**
     * Marque une action comme synchronisée
     */
    async markAsSynced(actionId) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            
            const action = store.get(actionId);
            action.onsuccess = () => {
                const item = action.result;
                item.status = 'synced';
                item.syncedAt = Date.now();
                
                const updateRequest = store.put(item);
                updateRequest.onsuccess = () => {
                    resolve();
                };
                updateRequest.onerror = () => {
                    reject(new Error('Erreur lors de la mise à jour'));
                };
            };
        });
    }
    
    /**
     * Incrémente le nombre de tentatives
     */
    async incrementAttempts(actionId) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            
            const action = store.get(actionId);
            action.onsuccess = () => {
                const item = action.result;
                item.attempts = (item.attempts || 0) + 1;
                
                if (item.attempts > 5) {
                    item.status = 'failed';
                }
                
                const updateRequest = store.put(item);
                updateRequest.onsuccess = () => {
                    resolve();
                };
                updateRequest.onerror = () => {
                    reject(new Error('Erreur lors de la mise à jour'));
                };
            };
        });
    }
    
    /**
     * Récupère les statistiques de synchronisation
     */
    async getSyncStats() {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const request = store.getAll();
            
            request.onsuccess = () => {
                const items = request.result;
                const stats = {
                    pending: items.filter(i => i.status === 'pending').length,
                    synced: items.filter(i => i.status === 'synced').length,
                    failed: items.filter(i => i.status === 'failed').length,
                    total: items.length,
                    isOnline: this.isOnline
                };
                resolve(stats);
            };
            
            request.onerror = () => {
                reject(new Error('Erreur lors de la lecture'));
            };
        });
    }
    
    /**
     * Vide la file d'attente (pour tests)
     */
    async clearQueue() {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                reject(new Error('IndexedDB non initialisée'));
                return;
            }
            
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const request = store.clear();
            
            request.onsuccess = () => {
                resolve();
            };
            
            request.onerror = () => {
                reject(new Error('Erreur lors du nettoyage'));
            };
        });
    }
}

// Initialisation globale
const offlineSync = new OfflineSyncClient();
