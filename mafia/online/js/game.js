const params =
new URLSearchParams(location.search);

const roomId =
params.get("id");

function loadRoom()
{
    fetch(
        "api/get_room.php?id="+roomId
    )
    .then(r=>r.json())
    .then(room=>{

        const players =
        document.getElementById(
            "players"
        );

        if(!players) return;

        let html="";

        room.players.forEach(player=>{

            html+=`
            <div>
            👤 ${player.name}
            </div>
            `;

        });

        players.innerHTML=html;

    })
    .catch(console.error);
}

setInterval(loadRoom,2000);

loadRoom();