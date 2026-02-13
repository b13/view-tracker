import { Chart } from "@frappe/charts";

class PageStatisticsChart {
	chartElement = document.querySelector('#bJS_txViewTrackerChart');
	chart = null;
	dataSets = document.querySelectorAll('.bJS_txViewTrackerDataSet');
	storageKey = 'txViewTracker_selectedDataSet';

	constructor() {
		if (this.chartElement) {

			this.dataSets.forEach(element => {
				element.addEventListener('click', e => {
					const data = JSON.parse(element.dataset.views);
					const type = element.dataset.type;
					localStorage.setItem(this.storageKey, element.dataset.title);
					if (this.chart?.type === type) {
						this.chart.update(data);
					} else {
						this.initialiseChart(element.dataset.title, data, type, JSON.parse(element.dataset.options));
					}
				});
			});

			const initialGraph = this.getInitialDataSet();
			this.initialiseChart(initialGraph.title, JSON.parse(initialGraph.views), initialGraph.type, JSON.parse(initialGraph.options));
		}
	}

	getInitialDataSet() {
		const savedTitle = localStorage.getItem(this.storageKey);
		if (savedTitle) {
			for (const element of this.dataSets) {
				if (element.dataset.title === savedTitle) {
					return element.dataset;
				}
			}
		}
		return this.dataSets.item(0).dataset;
	}

	initialiseChart(title, data, type, options) {
		this.chart = new Chart(this.chartElement, {
			title,
			data,
			type, // line | bar | axis-mixed | pie | percentage | heatmap
			barOptions: options.barOptions,
		});
	}
}

export default new PageStatisticsChart();
