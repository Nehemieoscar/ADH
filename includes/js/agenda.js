// Gestion de l'agenda
class AgendaManager {
    constructor() {
        this.calendar = null;
        this.miniCalendar = null;
        this.currentEvents = [];
        this.init();
    }

    init() {
        this.initCalendars();
        this.setupEventListeners();
        this.loadEvents();
    }

    initCalendars() {
        // Calendrier principal
        this.calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            locale: 'fr',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Aujourd\'hui',
                month: 'Mois',
                week: 'Semaine',
                day: 'Jour'
            },
            events: this.currentEvents,
            eventClick: (info) => {
                this.editEvent(info.event);
            },
            dateClick: (info) => {
                this.addEvent(info.date);
            },
            eventDrop: (info) => {
                this.updateEventDate(info.event);
            },
            eventResize: (info) => {
                this.updateEventDate(info.event);
            }
        });

        this.calendar.render();

        // Mini calendrier
        this.miniCalendar = new FullCalendar.Calendar(document.getElementById('mini-calendar'), {
            locale: 'fr',
            initialView: 'dayGridMonth',
            headerToolbar: { left: '', center: 'title', right: '' },
            height: 'auto',
            dateClick: (info) => {
                this.calendar.gotoDate(info.date);
            }
        });

        this.miniCalendar.render();
    }

    setupEventListeners() {
        // Modal d'événement
        document.getElementById('add-event-btn').addEventListener('click', () => {
            this.openEventModal();
        });

        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeEventModal();
            });
        });

        document.getElementById('event-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveEvent();
        });

        document.getElementById('delete-event-btn').addEventListener('click', () => {
            this.deleteEvent();
        });

        // Filtres
        document.querySelectorAll('input[name="filter"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.applyFilters();
            });
        });

        // Cours dans la sidebar
        document.querySelectorAll('.cours-item').forEach(item => {
            item.addEventListener('click', () => {
                const coursId = item.getAttribute('data-cours-id');
                this.filterByCours(coursId);
            });
        });
    }

    loadEvents() {
        // Charger les événements depuis l'API
        fetch('api/get-events.php')
            .then(response => response.json())
            .then(events => {
                this.currentEvents = events;
                this.calendar.removeAllEvents();
                this.calendar.addEventSource(events);
            })
            .catch(error => {
                console.error('Erreur chargement événements:', error);
            });
    }

    openEventModal(event = null) {
        const modal = document.getElementById('event-modal');
        const form = document.getElementById('event-form');
        const deleteBtn = document.getElementById('delete-event-btn');

        if (event) {
            // Mode édition
            document.getElementById('event-id').value = event.id;
            document.getElementById('event-title').value = event.title;
            document.getElementById('event-start').value = this.formatDateTimeForInput(event.start);
            document.getElementById('event-end').value = this.formatDateTimeForInput(event.end);
            document.getElementById('event-description').value = event.extendedProps.description || '';
            document.getElementById('event-type').value = event.extendedProps.type || 'personnel';
            document.getElementById('event-cours').value = event.extendedProps.cours_id || '';
            document.getElementById('event-color').value = event.backgroundColor || '#0052b4';
            
            deleteBtn.style.display = 'block';
            modal.querySelector('h3').textContent = 'Modifier l\'événement';
        } else {
            // Mode création
            form.reset();
            deleteBtn.style.display = 'none';
            modal.querySelector('h3').textContent = 'Ajouter un événement';
            
            // Définir les dates par défaut
            const now = new Date();
            document.getElementById('event-start').value = this.formatDateTimeForInput(now);
            
            const end = new Date(now.getTime() + 60 * 60 * 1000); // +1 heure
            document.getElementById('event-end').value = this.formatDateTimeForInput(end);
        }

        modal.style.display = 'block';
    }

    closeEventModal() {
        document.getElementById('event-modal').style.display = 'none';
    }

    addEvent(date) {
        this.openEventModal();
        document.getElementById('event-start').value = this.formatDateTimeForInput(date);
        
        const end = new Date(date.getTime() + 60 * 60 * 1000);
        document.getElementById('event-end').value = this.formatDateTimeForInput(end);
    }

    editEvent(event) {
        this.openEventModal(event);
    }

    async saveEvent() {
        const formData = new FormData(document.getElementById('event-form'));
        const eventData = {
            id: formData.get('id'),
            title: formData.get('title'),
            start: formData.get('start'),
            end: formData.get('end'),
            description: formData.get('description'),
            type: formData.get('type'),
            cours_id: formData.get('cours'),
            color: formData.get('color')
        };

        try {
            const response = await fetch('api/save-event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            });

            if (response.ok) {
                this.closeEventModal();
                this.loadEvents(); // Recharger les événements
            } else {
                throw new Error('Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la sauvegarde de l\'événement');
        }
    }

    async deleteEvent() {
        const eventId = document.getElementById('event-id').value;

        if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            return;
        }

        try {
            const response = await fetch('api/delete-event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: eventId })
            });

            if (response.ok) {
                this.closeEventModal();
                this.loadEvents(); // Recharger les événements
            } else {
                throw new Error('Erreur lors de la suppression');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de l\'événement');
        }
    }

    async updateEventDate(event) {
        const eventData = {
            id: event.id,
            start: event.start.toISOString(),
            end: event.end ? event.end.toISOString() : null
        };

        try {
            await fetch('api/update-event-date.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            });
        } catch (error) {
            console.error('Erreur mise à jour date:', error);
        }
    }

    applyFilters() {
        const selectedFilters = Array.from(document.querySelectorAll('input[name="filter"]:checked'))
            .map(checkbox => checkbox.value);

        // Filtrer les événements affichés
        this.calendar.getEvents().forEach(event => {
            const eventType = event.extendedProps.type || 'personnel';
            const shouldShow = selectedFilters.includes(eventType);
            event.setProp('display', shouldShow ? 'auto' : 'none');
        });
    }

    filterByCours(coursId) {
        this.calendar.getEvents().forEach(event => {
            const eventCoursId = event.extendedProps.cours_id;
            const shouldShow = !coursId || eventCoursId == coursId;
            event.setProp('display', shouldShow ? 'auto' : 'none');
        });
    }

    formatDateTimeForInput(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        return date.toISOString().slice(0, 16);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    window.agendaManager = new AgendaManager();
});