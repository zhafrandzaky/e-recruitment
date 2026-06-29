import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { Clock } from 'lucide-vue-next'
import BarList from '../../src/components/charts/BarList.vue'
import FunnelChart from '../../src/components/charts/FunnelChart.vue'
import StatFigure from '../../src/components/charts/StatFigure.vue'
import StatusBadge from '../../src/components/StatusBadge.vue'
import type { StatusFunnel } from '../../src/types'

describe('BarList', () => {
  const items = [
    { id: 'a', label: 'Job A', value: 10 },
    { id: 'b', label: 'Job B', value: 5 },
    { id: 'c', label: 'Job C', value: 0 },
  ]

  it('renders one row per item with its value', () => {
    const wrapper = mount(BarList, { props: { items } })
    const rows = wrapper.findAll('.bar-row')
    expect(rows).toHaveLength(3)
    expect(wrapper.text()).toContain('Job A')
    expect(wrapper.text()).toContain('10')
  })

  it('sizes bar widths proportionally to the max value', () => {
    const wrapper = mount(BarList, { props: { items } })
    const fills = wrapper.findAll('.bar-row__fill')
    expect(fills[0].attributes('style')).toContain('width: 100%')
    expect(fills[1].attributes('style')).toContain('width: 50%')
    expect(fills[2].attributes('style')).toContain('width: 0%')
  })

  it('shows the empty text when there are no items', () => {
    const wrapper = mount(BarList, { props: { items: [], emptyText: 'Kosong.' } })
    expect(wrapper.text()).toContain('Kosong.')
    expect(wrapper.findAll('.bar-row')).toHaveLength(0)
  })
})

describe('FunnelChart', () => {
  const funnel: StatusFunnel = { pending: 4, shortlisted: 3, rejected: 2, hired: 1 }

  it('renders all four stages with their counts and a correct total', () => {
    const wrapper = mount(FunnelChart, { props: { funnel } })
    const text = wrapper.text()
    expect(text).toContain('Menunggu')
    expect(text).toContain('Lolos Seleksi Berkas')
    expect(text).toContain('Diterima')
    expect(text).toContain('Ditolak')
    // total = 4 + 3 + 2 + 1
    expect(text).toContain('Total lamaran:')
    expect(text).toContain('10')
  })

  it('computes the share percentage of total', () => {
    const wrapper = mount(FunnelChart, { props: { funnel } })
    // pending share = 4/10 = 40%
    expect(wrapper.text()).toContain('40%')
  })
})

describe('StatFigure', () => {
  it('renders the value and unit when present', () => {
    const wrapper = mount(StatFigure, {
      props: { label: 'Waktu', value: 15, unit: 'hari', icon: Clock },
    })
    expect(wrapper.text()).toContain('15')
    expect(wrapper.text()).toContain('hari')
  })

  it('renders an em-dash and hint when the value is null', () => {
    const wrapper = mount(StatFigure, {
      props: { label: 'Waktu', value: null, emptyHint: 'Belum ada' },
    })
    expect(wrapper.text()).toContain('—')
    expect(wrapper.text()).toContain('Belum ada')
  })
})

describe('StatusBadge — hired', () => {
  it('renders the "Diterima" label for the hired status', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'hired' } })
    expect(wrapper.text()).toContain('Diterima')
    expect(wrapper.find('.badge--hired').exists()).toBe(true)
  })
})
