var apiURL = 'api/v1/nick';
var fiTime = new Intl.DateTimeFormat(
    'fi-FI', { year: 'numeric', month: 'numeric', day: 'numeric',
	       hour: 'numeric', minute: 'numeric' }
);

var app = new Vue({
    el: '#app',
    data: {
		user: {
			loading: 'Ladataan...',
		},
		response: {}
    },
    
    filters: {
		formatDate: function (ts) {
			return typeof ts === 'number' ? fiTime.format(1000*ts) : ''
		},
		formatMac: function (s) {
			return typeof s === 'string' ? s.match(/.{1,2}/g).join(':') : ''
		}
		},

		created: function () {
		this.fetchData()
    },

    methods: {
		fetchData: function () {
			var xhr = new XMLHttpRequest()
			var self = this
			xhr.open('GET', apiURL)
			xhr.onload = function () {
			self.user = JSON.parse(xhr.responseText)
			}
			xhr.send()
		},

		handleResponse: function(response, message) {
			var self = this;
			var out = JSON.parse(response);
			if (out.error) {
				self.response = {
					message: out.error,
					success: false,
					error: true
				}
			}
			else if(out.success) {
				self.response = {
					message: message,
					success: true,
					error: false
				}
			}
			var timeout = setTimeout(function() {
				self.response = {
					message: '',
					success: false,
					error: false
				}
				clearTimeout(timeout);
			}, 5000);
			self.fetchData();
		},

		setNick: function (nick, stealth) {
			var xhr = new XMLHttpRequest()
			var self = this
			xhr.open('PUT', apiURL + '?nick=' + encodeURIComponent(nick === null ? '' : nick) + '&stealth=' + encodeURIComponent(stealth ? '1' : '0'))
			xhr.onload = function () {
				self.handleResponse(xhr.responseText, 'Nimimerkki päivitetty');
			}
			xhr.send()
		},
		
		delNick: function () {
			var xhr = new XMLHttpRequest()
			var self = this
			xhr.open('DELETE', apiURL)
			xhr.onload = function () {
				self.handleResponse(xhr.responseText, 'Nimimerkki poistettu');
			}
			xhr.send()
		}

    }
})

