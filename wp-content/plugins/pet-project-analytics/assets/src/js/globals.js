import {request} from './util/api.js'
import * as dates from './util/dates.js'
import { BlockComponent } from './components/block-components.js'

Object.assign(window.pp_analytics, {
  components: {BlockComponent},
  util: {request, dates}
})
