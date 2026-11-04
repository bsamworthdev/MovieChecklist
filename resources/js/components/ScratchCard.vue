<template>
    <div class="card movieCard" :class="{ watched: hasWatched }" @click="toggleWatched">
        <div class="card-body movieImage" :style="{ backgroundImage: `url(${movie.image_url_small_extended})` }">
            <h4>{{ movie.name }} ({{ movie.year }})</h4>
            <div class="rank" :class="{ rounded: (movie.index==movie.top250_rank) }">
                <span class="filtered_rank">{{ movie.index }}</span>
                <span v-if="movie.index!=movie.top250_rank" class="actual_rank">({{ rankTidy }})</span>
            </div>
            <div v-if="hasWatched" class="favourite"
                    :class="{ selected: isFavourite, hovering: favouriteHover, hasFriends: user.friendsCount > 0 }" 
                    @mouseover="favouriteHover = true"
                    @mouseleave="favouriteHover = false"
                    @click="toggleIsFavourite($event)">
                <i class="fa fa-heart heart filled"></i>
                <i class="far fa-heart heart outline"></i>
            </div>
            <div v-else class="watchList"
                    :class="{ selected: isOnWatchList, hovering: watchListHover, hasFriends: user.friendsCount > 0 }" 
                    @mouseover="watchListHover = true"
                    @mouseleave="watchListHover = false"
                    @click="toggleIsOnWatchlist($event)">   
                <i class="fas fa-list-alt watchListIcon" :title="watchListTitle"></i>
                <i class="fas fa-check-circle watchListTick"></i>
                <i class="fas fa-plus-circle watchListAdd"></i>
            </div>
            <div v-if="user.friendsCount > 0" class="friends" 
                :title="movie.friendsWatched + ' of your friends also watched this'"
                @click="showFriendsPopup($event)">
                <i class="fas fa-user friendsIcon"></i>
                <span class="friendsCount"> x {{ movie.friendsWatched }}</span>
            </div>
            <div class="rating">
                <i class="fa fa-star star"></i>
                <span>{{ ratingShort }}</span>
            </div>
                <div class="platforms">
                <div v-if="user.role=='editor' || user.role=='admin'">
                    <div :class="{'dimmed': (isOnNetflix == 0) }" @click="editMovieDetailsClicked($event, 'netflix')" class="platform netflix" title="On netflix"></div>
                    <div :class="{'dimmed': (isOnAmazon == 0) }" @click="editMovieDetailsClicked($event, 'amazon')" class="platform amazon" title="On amazon video"></div>
                    <div :class="{'dimmed': (isOnNowtv == 0) }" @click="editMovieDetailsClicked($event, 'nowtv')" class="platform nowtv" title="On Now TV"></div>
                    <div :class="{'dimmed': (isOnDisneyPlus == 0) }" @click="editMovieDetailsClicked($event, 'disney_plus')" class="platform disney_plus" title="On Disney Plus"></div>
                    <div :class="{'dimmed': (isOnHboMax == 0) }" @click="editMovieDetailsClicked($event, 'hbo_max')" class="platform hbo_max" title="On HBO Max"></div>
                    <div :class="{'dimmed': (isOnAppleTv == 0) }" @click="editMovieDetailsClicked($event, 'apple_tv')" class="platform apple_tv" title="On Apple TV"></div>
                    <div :class="{'dimmed': (isOnParamountPlus == 0) }" @click="editMovieDetailsClicked($event, 'paramount_plus')" class="platform paramount_plus" title="On Paramount+"></div>
                    <div :class="{'dimmed': (isOnBbcIplayer == 0) }" @click="editMovieDetailsClicked($event, 'bbc_iplayer')" class="platform bbc_iplayer" title="On BBC iPlayer"></div>
                    <div :class="{'dimmed': (isOnItvx == 0) }" @click="editMovieDetailsClicked($event, 'itvx')" class="platform itvx" title="On ITVX"></div>
                    <div :class="{'dimmed': (isOnChannel4 == 0) }" @click="editMovieDetailsClicked($event, 'channel4')" class="platform channel4" title="On Channel 4"></div>
                </div>
                <div v-else>
                    <div v-if="isOnNetflix == 1" class="platform netflix" title="On netflix"></div>
                    <div v-if="isOnAmazon  == 1" class="platform amazon" title="On amazon video"></div>
                    <div v-if="isOnNowtv  == 1" class="platform nowtv" title="On Now TV"></div>
                    <div v-if="isOnDisneyPlus  == 1" class="platform disney_plus" title="On Disney Plus"></div>
                    <div v-if="isOnHboMax  == 1" class="platform hbo_max" title="On HBO Max"></div>
                    <div v-if="isOnAppleTv  == 1" class="platform apple_tv" title="On Apple TV"></div>
                    <div v-if="isOnParamountPlus  == 1" class="platform paramount_plus" title="On Paramount+"></div>
                    <div v-if="isOnBbcIplayer  == 1" class="platform bbc_iplayer" title="On BBC iPlayer"></div>
                    <div v-if="isOnItvx  == 1" class="platform itvx" title="On ITVX"></div>
                    <div v-if="isOnChannel4  == 1" class="platform channel4" title="On Channel 4"></div>
                </div>                 
            </div>
            <div class="tickContainer">
                <i v-if="hasWatched" class="fa fa-check tick"></i>
            </div>
        </div>
        <div class="footer" @click="openIMDBModal($event)">
            View on IMDb
            <i class="externalLink fas fa-external-link-alt"></i>
        </div>
    </div>
</template>

<script>
    import Toasted from 'vue-toasted';

    Vue.use(Toasted, {
        iconPack: 'custom-class'
    });
    export default {
        props: {
            movie: Object,
            user: Object
        },
        components: {
            Toasted
        },
        methods: {
            toggleWatched(){
                if (this.savingWatched) return;
                this.savingWatched = true;
                const watched = !this.hasWatched;
                axios.post('/saveMovieUser',{
                    movie_id:this.movie.id,
                    watched:watched
                })
                .then((response) => {
                    this.hasWatched = watched;
                    this.movieStatusChanged();
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                })
                .finally(() => {
                    this.savingWatched = false;
                });
            },
            movieStatusChanged() {
                this.$set(this.movie, 'watched', this.hasWatched);
                this.$emit('movieStatusChanged', this.hasWatched);
            },
            toggleIsFavourite(e){
                e.stopPropagation();
                axios.post('/setMovieAsFavourite',{
                    movie_id:this.movie.id,
                    favourite:!this.isFavourite                    
                })
                .then((response) => {
                    this.isFavourite = !this.isFavourite;
                    if (this.isFavourite) {
                        this.$toasted.success('Added Favourite: ' + this.movie.name, {
                            action : {
                                text : 'close',
                                onClick : (e, toastObject) => {
                                    toastObject.goAway(0);
                                }
                            },
                            icon: 'fa fa-check',
                            duration : 4000
                        });
                    } else {
                        this.$toasted.error('Removed Favourite: ' + this.movie.name, {
                            action : {
                                text : 'close',
                                onClick : (e, toastObject) => {
                                    toastObject.goAway(0);
                                }
                            },
                            icon: 'fa fa-times',
                            duration : 4000
                        });
                    }
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            toggleIsOnWatchlist(e){
                e.stopPropagation();
                axios.post('/toggleMovieInWatchList',{
                    movie_id:this.movie.id,
                    onWatchList:!this.isOnWatchList                 
                })
                .then((response) => {
                    this.isOnWatchList = !this.isOnWatchList;
                    // location.reload();
                    if (this.isOnWatchList){
                        this.movie.on_watch_list = '1';
                        this.$emit('addMovieToWatchList', this.movie);
                    } else {
                        this.movie.on_watch_list = '0';
                        this.$emit('removeMovieFromWatchList', this.movie);
                    }
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            showFriendsPopup(e) {
                e.stopPropagation();
                this.$emit('showFriendsPopup', this.movie);
            },
            editMovieDetailsClicked(e, platform) {
                e.stopPropagation();
                this.$emit('editMovieDetailsClicked', platform, this.movie);
            },
            openIMDBModal(e){
                e.stopPropagation();
                // window.location.href = 'https://imdb.com/title/' + this.movie.imdb_id;
                window.open('https://imdb.com/title/' + this.movie.imdb_id, '_blank');
                //this.$emit('openIMDBModal', this.movie);
            }
        },
        computed: {
            ratingShort: function (){
                if (this.movie.rating) {
                    return parseFloat(this.movie.rating).toFixed(1);
                } else {
                    return 'n/a';
                }
            },
            watchListTitle: function (){
                var title;
                if (this.isOnWatchList){
                    title = 'On Watch List';
                } else {
                    title = 'Add To Watch List';
                }
                return title;
            },
            rankTidy: function(){
                if (this.movie.top250_rank < 250){
                    return this.movie.top250_rank;
                } else {
                    return '>250';
                }
            },
        },
        data() {
            return {
                hasWatched: (this.movie.watched == 1),
                savingWatched: false,
                isFavourite: (this.movie.favourite == 1),
                isOnWatchList: (this.movie.on_watch_list == 1),
                isOnNetflix: (this.movie.on_netflix == 1),
                isOnAmazon: (this.movie.on_amazon == 1),
                isOnNowtv: (this.movie.on_nowtv == 1),
                isOnDisneyPlus: (this.movie.on_disney_plus == 1),
                isOnHboMax: (this.movie.on_hbo_max == 1),
                isOnAppleTv: (this.movie.on_apple_tv == 1),
                isOnParamountPlus: (this.movie.on_paramount_plus == 1),
                isOnBbcIplayer: (this.movie.on_bbc_iplayer == 1),
                isOnItvx: (this.movie.on_itvx == 1),
                isOnChannel4: (this.movie.on_channel4 == 1),
                favouriteHover: false,
                watchListHover: false
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>

<style scoped src="./catalogue-card.css"></style>
